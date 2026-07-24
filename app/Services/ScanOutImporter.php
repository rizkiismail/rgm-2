<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ScanOutImporter
{
    /**
     * Urutan kolom persis seperti pada file export "Monitoring Raking Scan Out":
     * No, Row, Customer, Code Item, Part Name, Part No, Model, Barcode, Scan By NIK,
     * Scan By Name, Outgoing No, Customer To, Outgoing Type, Scan Date, QTY, Unit
     */
    protected array $columns = [
        'source_no',
        'row_location',
        'customer_name',
        'code_item',
        'part_name',
        'part_no',
        'model',
        'barcode',
        'scan_by_nik',
        'scan_by_name',
        'outgoing_no',
        'customer_to',
        'outgoing_type',
        'scan_date',
        'qty',
        'unit',
    ];

    /**
     * Import file (.xlsx/.xls) "Monitoring Raking Scan Out" ke tabel scan_outs.
     *
     * @return array{inserted:int, skipped:int, total:int}
     */
    public function importFromFile(string $filePath, string $batchLabel, bool $truncateFirst = false): array
    {
        $rows = $this->parseRows($filePath);

        if (count($rows) === 0) {
            throw new \RuntimeException(
                'Tidak ada baris data yang terdeteksi. Pastikan file adalah hasil export '.
                '"Monitoring Raking Scan Out" dengan header (No, Row, Customer, Code Item, ...).'
            );
        }

        if ($truncateFirst) {
            DB::table('scan_outs')->truncate();
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

                // Baris tanpa Barcode tidak bisa diidentifikasi unik, lewati.
                $barcode = $this->toNullableString($data['barcode']);
                if ($barcode === null) {
                    $skipped++;

                    continue;
                }

                $scanDate = $this->toDateTime($data['scan_date']);

                $records[] = [
                    'source_no' => $this->toInt($data['source_no']),
                    'row_location' => $this->toNullableString($data['row_location']),
                    'customer_name' => $this->normalizeCustomer($data['customer_name']),
                    'code_item' => $this->toNullableString($data['code_item']),
                    'part_name' => $this->toNullableString($data['part_name']),
                    'part_no' => $this->toNullableString($data['part_no']),
                    'model' => $this->toNullableString($data['model']),
                    'barcode' => $barcode,
                    'scan_by_nik' => $this->toNullableString($data['scan_by_nik']),
                    'scan_by_name' => $this->toNullableString($data['scan_by_name']),
                    'outgoing_no' => $this->toNullableString($data['outgoing_no']),
                    'customer_to' => $this->normalizeCustomer($data['customer_to']),
                    'outgoing_type' => $this->toNullableString($data['outgoing_type']),
                    'scan_date' => $scanDate,
                    'qty' => $this->toDecimal($data['qty']),
                    'unit' => $this->toNullableString($data['unit']),
                    'import_batch' => $batchLabel,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($records) > 0) {
                // upsert berdasarkan kombinasi barcode + scan_date + outgoing_no supaya
                // upload ulang file yang sama tidak menggandakan data.
                DB::table('scan_outs')->upsert(
                    $records,
                    ['barcode', 'scan_date', 'outgoing_no'],
                    [
                        'source_no', 'row_location', 'customer_name', 'code_item', 'part_name',
                        'part_no', 'model', 'scan_by_nik', 'scan_by_name', 'customer_to',
                        'outgoing_type', 'qty', 'unit', 'import_batch', 'updated_at',
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
     * Baca seluruh baris data dari file Excel, dimulai setelah baris header.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function parseRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = count($this->columns);

        // Cari baris header (baris yang mengandung "No" & "Row" / "Customer") supaya
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
     * Cari baris yang berisi header kolom "No" dan "Customer" pada 10 baris pertama.
     * Kalau tidak ditemukan, asumsikan baris ke-3 (format standar hasil export).
     */
    protected function findHeaderRow($sheet, int $highestRow): int
    {
        $scanLimit = min($highestRow, 10);

        for ($r = 1; $r <= $scanLimit; $r++) {
            $firstCell = trim((string) $sheet->getCell('A'.$r)->getFormattedValue());
            $thirdCell = trim((string) $sheet->getCell('C'.$r)->getFormattedValue());

            if (strcasecmp($firstCell, 'No') === 0 && stripos($thirdCell, 'Customer') !== false) {
                return $r;
            }
        }

        return 3;
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
     * Tanggal scan pada file export berformat teks "dd-mm-yyyy HH:mm:ss"
     * (mis. "02-07-2026 22:37:37"), tapi ditangani juga jika Excel menyimpannya
     * sebagai tanggal asli (serial number).
     */
    protected function toDateTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // lanjut coba parse sebagai teks di bawah
            }
        }

        try {
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})\s+(\d{1,2}):(\d{2}):(\d{2})$/', $value)) {
                return Carbon::createFromFormat('d-m-Y H:i:s', $value)->format('Y-m-d H:i:s');
            }

            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $value)) {
                return Carbon::createFromFormat('d-m-Y', $value)->startOfDay()->format('Y-m-d H:i:s');
            }

            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
