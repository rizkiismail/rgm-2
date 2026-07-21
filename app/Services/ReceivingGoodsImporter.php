<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $html = file_get_contents($filePath);

        if ($html === false || trim($html) === '') {
            throw new \RuntimeException('File kosong atau tidak dapat dibaca.');
        }

        $rows = $this->parseRows($html);

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

        foreach (array_chunk($rows, 500) as $chunk) {
            $records = [];

            foreach ($chunk as $row) {
                if (count($row) !== 15) {
                    $skipped++;

                    continue;
                }

                $data = array_combine($this->columns, $row);
                $records[] = [
                    'source_no' => $this->toInt($data['source_no']),
                    'date_income' => $this->toDateTime($data['date_income']),
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

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($rows),
        ];
    }

    /**
     * Ambil semua baris <tr><td>...</td></tr> dari tabel data (id="resultData").
     *
     * @return array<int, array<int, string>>
     */
    protected function parseRows(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // Encoding file export ini umumnya UTF-8; paksa DOMDocument membacanya sebagai UTF-8.
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Cari tbody#resultData; jika tidak ketemu, fallback ke tabel data ke-2.
        $body = $xpath->query('//tbody[@id="resultData"]')->item(0);

        if ($body === null) {
            $tables = $xpath->query('//table');
            $body = $tables->length > 1 ? $tables->item($tables->length - 1) : null;
        }

        if ($body === null) {
            return [];
        }

        $rows = [];
        foreach ($xpath->query('.//tr', $body) as $tr) {
            $cells = [];
            foreach ($xpath->query('.//td', $tr) as $td) {
                $cells[] = trim($td->textContent);
            }
            if (count($cells) > 0) {
                $rows[] = $cells;
            }
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
