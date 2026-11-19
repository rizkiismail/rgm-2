<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReceivingGoodsImporter
{
    /**
     * Urutan kolom persis seperti pada tabel hasil export "Monitoring Receiving Goods".
     */
    protected array $columns = [
        'source_no',
        'date_income',
        'bsthp_date',
        'bsthp_no',
        'verify_no',
        'verify_by',
        'code_item',
        'part_name',
        'part_no',
        'model',
        'qty',
        'unit',
        'bsthp_barcode_no',
        'label_barcode_no',
        'customer',
    ];

    /**
     * Import file (raw HTML yang berekstensi .xls, atau .html/.htm) ke tabel receiving_goods.
     *
     * @return array{inserted:int, updated:int, skipped:int, total:int}
     */
    public function importFromFile(string $filePath, string $batchLabel, bool $truncateFirst = false): array
    {
        // File export bisa belasan MB (puluhan ribu baris); jangan sampai kena
        // batas max_execution_time / memory_limit bawaan PHP.
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $html = file_get_contents($filePath);

        if ($html === false || trim($html) === '') {
            throw new \RuntimeException('File kosong atau tidak dapat dibaca.');
        }

        $rows = $this->parseRows($html);
        unset($html); // bebaskan memori (file bisa >16MB)

        if (count($rows) === 0) {
            throw new \RuntimeException(
                'Tidak ada baris data yang terdeteksi. Pastikan file adalah hasil export '.
                '"Monitoring Receiving Goods" (tabel dengan id="resultData").'
            );
        }

        if ($truncateFirst) {
            DB::table('receiving_goods')->truncate();
        }

        $inserted = 0;
        $skipped = 0;
        $now = now();
        $minDate = null; // rentang tanggal data yang diimport, untuk memperbarui ringkasan
        $maxDate = null;

        // Satu transaksi untuk seluruh import: jauh lebih cepat daripada commit
        // per-batch (tiap commit memaksa flush ke disk, apalagi tabel ini punya
        // banyak index + generated column).
        DB::beginTransaction();

        try {
        foreach (array_chunk($rows, 1000) as $chunk) {
            $records = [];

            foreach ($chunk as $row) {
                if (count($row) !== 15) {
                    $skipped++;

                    continue;
                }

                $data = array_combine($this->columns, $row);
                $dateIncome = $this->toDateTime($data['date_income']);
                if ($dateIncome !== null) {
                    $minDate = $minDate === null || $dateIncome < $minDate ? $dateIncome : $minDate;
                    $maxDate = $maxDate === null || $dateIncome > $maxDate ? $dateIncome : $maxDate;
                }

                $records[] = [
                    'source_no' => $this->toInt($data['source_no']),
                    'date_income' => $dateIncome,
                    'bsthp_date' => $this->toDateTime($data['bsthp_date']),
                    'bsthp_no' => $this->toNullableString($data['bsthp_no']),
                    'verify_no' => $this->toNullableString($data['verify_no']),
                    'verify_by' => $this->toNullableString($data['verify_by']),
                    'code_item' => $this->toNullableString($data['code_item']),
                    'part_name' => $this->toNullableString($data['part_name']),
                    'part_no' => $this->toNullableString($data['part_no']),
                    'model' => $this->toNullableString($data['model']),
                    'qty' => $this->toDecimal($data['qty']),
                    'unit' => $this->toNullableString($data['unit']),
                    'bsthp_barcode_no' => $this->toNullableString($data['bsthp_barcode_no']),
                    'label_barcode_no' => $this->toNullableString($data['label_barcode_no']),
                    'customer' => $this->normalizeCustomer($data['customer']),
                    'import_batch' => $batchLabel,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($records) > 0) {
                // upsert berdasarkan kombinasi bsthp_no + label_barcode_no supaya
                // upload ulang file yang sama tidak menggandakan data.
                DB::table('receiving_goods')->upsert(
                    $records,
                    ['bsthp_no', 'label_barcode_no'],
                    [
                        'source_no', 'date_income', 'bsthp_date', 'verify_no', 'verify_by',
                        'code_item', 'part_name', 'part_no', 'model', 'qty', 'unit',
                        'bsthp_barcode_no', 'customer', 'import_batch', 'updated_at',
                    ]
                );
                $inserted += count($records);
            }
        }

        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // Perbarui tabel ringkasan dashboard: seluruhnya jika mode "Ganti Semua Data",
        // selain itu hanya rentang tanggal yang ada di file ini.
        if (Schema::hasTable('receiving_goods_rollup')) {
            $rollup = app(ReceivingGoodsRollup::class);

            if ($truncateFirst) {
                $rollup->rebuild();
            } elseif ($minDate !== null) {
                $rollup->rebuild(substr($minDate, 0, 10), substr($maxDate, 0, 10));
            }
        }

        // Naikkan versi data supaya cache hasil dashboard otomatis tidak terpakai lagi.
        Cache::forever('receiving_goods.data_version', (string) microtime(true));

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($rows),
        ];
    }

    /**
     * Ambil semua baris <tr><td>...</td></tr> dari tabel data (id="resultData").
     *
     * Sengaja TIDAK memakai DOMDocument/DOMXPath: membangun DOM untuk file HTML
     * 16MB+ (puluhan ribu baris) sangat lambat dan boros memori, dan itu salah
     * satu penyebab "Maximum execution time of 60 seconds exceeded". Di sini
     * HTML dipindai langsung dengan strpos/regex kecil per baris (linear).
     *
     * @return array<int, array<int, string>>
     */
    protected function parseRows(string $html): array
    {
        $endTag = '</tbody>';
        $start = strpos($html, 'id="resultData"');

        if ($start === false) {
            // Fallback: tabel terakhir pada file.
            $start = strripos($html, '<table');
            $endTag = '</table>';

            if ($start === false) {
                return [];
            }
        }

        $end = stripos($html, $endTag, $start);
        $segment = $end === false ? substr($html, $start) : substr($html, $start, $end - $start);

        $rows = [];
        $pos = 0;

        while (($trStart = stripos($segment, '<tr', $pos)) !== false) {
            $trEnd = stripos($segment, '</tr>', $trStart);
            if ($trEnd === false) {
                break;
            }

            $tr = substr($segment, $trStart, $trEnd - $trStart);
            $pos = $trEnd + 5;

            if (! preg_match_all('~<td\b[^>]*>(.*?)</td>~is', $tr, $matches)) {
                continue;
            }

            $cells = [];
            foreach ($matches[1] as $td) {
                $text = html_entity_decode(strip_tags($td), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // trim juga non-breaking space (\xC2\xA0) yang tidak dibuang trim() biasa.
                $cells[] = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $text) ?? trim($text);
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    protected function toInt(?string $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    protected function toDecimal(?string $value): ?float
    {
        $value = trim((string) $value);
        $value = str_replace(',', '', $value);

        return $value === '' || ! is_numeric($value) ? null : (float) $value;
    }

    protected function toNullableString(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        return $value;
    }

    /**
     * Sama seperti toNullableString(), tapi juga merapikan spasi ganda pada nama
     * customer (mis. "ROKI  INDONESIA, PT." -> "ROKI INDONESIA, PT.") supaya
     * cocok dengan nama pada tabel master `customers` saat dicocokkan Line-nya.
     */
    protected function normalizeCustomer(?string $value): ?string
    {
        $value = $this->toNullableString($value);
        if ($value === null) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    protected function toDateTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
