<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BalanceReturImporter
{
    /**
     * Urutan kolom persis seperti pada file export "Monitoring Balance Retur":
     * No, Date Retur, No Retur, Rev No, No From Customer, Customer Name, Code Item,
     * Part No, Part Name, Model, Product Status, QTY Retur, Unit, QTY Receiving Part,
     * QTY Pending Receiving Part, Status, QTY Delivery Part, QTY Pending Delivery Part,
     * Status, Stock Realtime, Final Status, Note, PIC PPIC DELIVERY
     */
    protected array $columns = [
        'source_no',
        'date_retur',
        'no_retur',
        'rev_no',
        'no_from_customer',
        'customer_name',
        'code_item',
        'part_no',
        'part_name',
        'model',
        'product_status',
        'qty_retur',
        'unit',
        'qty_receiving_part',
        'qty_pending_receiving_part',
        'status_receiving',
        'qty_delivery_part',
        'qty_pending_delivery_part',
        'status_delivery',
        'stock_realtime',
        'final_status',
        'note',
        'pic_ppic_delivery',
    ];

    /**
     * Import file (.xlsx/.xls) "Monitoring Balance Retur" ke tabel balance_returs.
     *
     * @return array{inserted:int, updated:int, skipped:int, total:int}
     */
    public function importFromFile(string $filePath, string $batchLabel, bool $truncateFirst = false): array
    {
        $rows = $this->parseRows($filePath);

        if (count($rows) === 0) {
            throw new \RuntimeException(
                'Tidak ada baris data yang terdeteksi. Pastikan file adalah hasil export '.
                '"Monitoring Balance Retur" dengan header pada baris ke-2 (No, Date Retur, No Retur, ...).'
            );
        }

        if ($truncateFirst) {
            DB::table('balance_returs')->truncate();
        }

        $inserted = 0;
        $skipped = 0;
        $now = now();

        foreach (array_chunk($rows, 500) as $chunk) {
            $records = [];

            foreach ($chunk as $row) {
                if (count($row) !== count($this->columns)) {
                    $skipped++;

                    continue;
                }

                $data = array_combine($this->columns, $row);

                // Baris tanpa No Retur / Code Item tidak bisa diidentifikasi unik, lewati.
                $noRetur = $this->toNullableString($data['no_retur']);
                $codeItem = $this->toNullableString($data['code_item']);
                if ($noRetur === null || $codeItem === null) {
                    $skipped++;

                    continue;
                }

                $records[] = [
                    'source_no' => $this->toInt($data['source_no']),
                    'date_retur' => $this->toDate($data['date_retur']),
                    'no_retur' => $noRetur,
                    'rev_no' => $this->toNullableString($data['rev_no']),
                    'no_from_customer' => $this->toNullableString($data['no_from_customer']),
                    'customer_name' => $this->normalizeCustomer($data['customer_name']),
                    'code_item' => $codeItem,
                    'part_no' => $this->toNullableString($data['part_no']),
                    'part_name' => $this->toNullableString($data['part_name']),
                    'model' => $this->toNullableString($data['model']),
                    'product_status' => $this->toNullableString($data['product_status']),
                    'qty_retur' => $this->toDecimal($data['qty_retur']),
                    'unit' => $this->toNullableString($data['unit']),
                    'qty_receiving_part' => $this->toDecimal($data['qty_receiving_part']),
                    'qty_pending_receiving_part' => $this->toDecimal($data['qty_pending_receiving_part']),
                    'status_receiving' => $this->normalizeStatus($data['status_receiving']),
                    'qty_delivery_part' => $this->toDecimal($data['qty_delivery_part']),
                    'qty_pending_delivery_part' => $this->toDecimal($data['qty_pending_delivery_part']),
                    'status_delivery' => $this->normalizeStatus($data['status_delivery']),
                    'stock_realtime' => $this->toDecimal($data['stock_realtime']),
                    'final_status' => $this->normalizeStatus($data['final_status']),
                    'note' => $this->toNullableString($data['note']),
                    'pic_ppic_delivery' => $this->toNullableString($data['pic_ppic_delivery']),
                    'import_batch' => $batchLabel,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($records) > 0) {
                // upsert berdasarkan kombinasi no_retur + code_item supaya upload ulang
                // file yang sama tidak menggandakan data.
                DB::table('balance_returs')->upsert(
                    $records,
                    ['no_retur', 'code_item'],
                    [
                        'source_no', 'date_retur', 'rev_no', 'no_from_customer', 'customer_name',
                        'part_no', 'part_name', 'model', 'product_status', 'qty_retur', 'unit',
                        'qty_receiving_part', 'qty_pending_receiving_part', 'status_receiving',
                        'qty_delivery_part', 'qty_pending_delivery_part', 'status_delivery',
                        'stock_realtime', 'final_status', 'note', 'pic_ppic_delivery',
                        'import_batch', 'updated_at',
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
     * Baca seluruh baris data dari file Excel, dimulai dari baris ke-3
     * (baris 1 = judul, baris 2 = header kolom).
     *
     * @return array<int, array<int, mixed>>
     */
    protected function parseRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = count($this->columns);

        // Cari baris header (baris yang mengandung "No" & "Date Retur") supaya
        // tidak bergantung pada posisi baris judul yang mungkin berbeda-beda.
        $headerRow = $this->findHeaderRow($sheet, $highestRow);
        $firstDataRow = $headerRow + 1;

        $rows = [];
        for ($r = $firstDataRow; $r <= $highestRow; $r++) {
            $cells = [];
            $isEmpty = true;

            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                $coordinate = Coordinate::stringFromColumnIndex($c).$r;
                $value = $sheet->getCell($coordinate)->getFormattedValue();
                $value = trim((string) $value);
                if ($value !== '') {
                    $isEmpty = false;
                }
                $cells[] = $value;
            }

            if (! $isEmpty) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * Cari baris yang berisi header kolom "No" dan "Date Retur" pada 10 baris pertama.
     * Kalau tidak ditemukan, asumsikan baris ke-2 (format standar hasil export).
     */
    protected function findHeaderRow($sheet, int $highestRow): int
    {
        $scanLimit = min($highestRow, 10);

        for ($r = 1; $r <= $scanLimit; $r++) {
            $firstCell = trim((string) $sheet->getCell('A'.$r)->getFormattedValue());
            $secondCell = trim((string) $sheet->getCell('B'.$r)->getFormattedValue());

            if (strcasecmp($firstCell, 'No') === 0 && stripos($secondCell, 'Date') !== false) {
                return $r;
            }
        }

        return 2;
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
     * customer supaya cocok dengan nama pada tabel master `customers`.
     */
    protected function normalizeCustomer(?string $value): ?string
    {
        $value = $this->toNullableString($value);
        if ($value === null) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Rapikan nilai status (CLOSE/OPEN) jadi huruf besar seragam.
     */
    protected function normalizeStatus(?string $value): ?string
    {
        $value = $this->toNullableString($value);

        return $value === null ? null : strtoupper($value);
    }

    /**
     * Tanggal pada file export berformat teks "dd-mm-yyyy" (mis. "21-07-2026"),
     * tapi ditangani juga jika Excel menyimpannya sebagai tanggal asli (serial number).
     */
    protected function toDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // lanjut coba parse sebagai teks di bawah
            }
        }

        try {
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $value, $m)) {
                return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
