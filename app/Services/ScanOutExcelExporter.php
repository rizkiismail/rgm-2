<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanOutExcelExporter
{
    protected const HEADER_FILL = '1F4E78';

    protected const SECTION_FILL = 'D9E2F3';

    /**
     * Build dan stream file Excel berisi Ringkasan (Summary Cards) dan tabel Detail Data
     * sesuai filter yang sedang aktif.
     *
     * @param  array  $summary  Hasil dari ScanOutController::buildSummary()
     * @param  Collection  $rows  Seluruh baris ScanOut hasil filter (tidak dipaginasi)
     * @param  array  $filtersMeta  ['date_from' => ?Carbon, 'date_to' => ?Carbon, 'customer' => ?string, 'outgoing_type' => ?string, 'pic' => ?string, 'q' => ?string]
     */
    public function export(array $summary, Collection $rows, array $filtersMeta): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->buildRingkasanSheet($spreadsheet, $summary, $filtersMeta, $rows->count());
        $this->buildDetailSheet($spreadsheet, $rows);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Monitoring_Raking_Scan_Out_'.now()->format('Ymd_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Sheet 1: Ringkasan (Summary Cards) — mencerminkan semua kartu yang tampil di dashboard.
     */
    protected function buildRingkasanSheet(Spreadsheet $spreadsheet, array $summary, array $filtersMeta, int $totalRowsExported): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan');

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(22);

        $row = 1;

        $sheet->setCellValue("A{$row}", 'MONITORING RAKING SCAN OUT');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $periode = ($filtersMeta['date_from'] ? Carbon::parse($filtersMeta['date_from'])->format('d-m-Y') : '-')
            .' s/d '.
            ($filtersMeta['date_to'] ? Carbon::parse($filtersMeta['date_to'])->format('d-m-Y') : '-');

        $filterRows = [
            'Periode' => $periode,
            'Customer' => $filtersMeta['customer'] ?: 'Semua Customer',
            'Outgoing Type' => $filtersMeta['outgoing_type'] ?: 'Semua Tipe',
            'PIC Scan' => $filtersMeta['pic'] ?: 'Semua PIC',
            'Kata Kunci' => $filtersMeta['q'] ?: '-',
            'Waktu Export' => now()->format('d-m-Y H:i').' WIB',
        ];

        foreach ($filterRows as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }
        $row++;

        $row = $this->writeSectionHeader($sheet, $row, 'RINGKASAN UMUM');
        $row = $this->writeMetricRow($sheet, $row, 'Total Scan Out (Baris)', $summary['totalScanOut']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Customer (Unik)', $summary['totalCustomer']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Code Item (Unik)', $summary['totalCodeItem']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Barcode (Unik)', $summary['totalBarcode']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Outgoing (Unik)', $summary['totalOutgoing']);
        $row = $this->writeMetricRow($sheet, $row, 'Total PIC Scan (Unik)', $summary['totalPic']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Qty Scan Out', (float) $summary['totalQty']);
        $row++;

        $row = $this->writeSectionHeader($sheet, $row, 'OUTGOING BERDASARKAN TIPE');
        foreach ($summary['outgoingTypeCount'] as $type => $count) {
            $row = $this->writeMetricRow($sheet, $row, $type, $count);
        }
        $row++;

        $sheet->setCellValue("A{$row}", 'Jumlah baris yang diexport ke sheet "Detail Data": '.number_format($totalRowsExported));
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('6C757D');

        $sheet->setSelectedCell('A1');
    }

    protected function writeSectionHeader($sheet, int $row, string $title): int
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('1F4E78');
        $sheet->getStyle("A{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::SECTION_FILL);

        return $row + 1;
    }

    protected function writeMetricRow($sheet, int $row, string $label, int|float|null $value): int
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValueExplicit("B{$row}", $this->normalizeNumericValue($value), DataType::TYPE_NUMERIC);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return $row + 1;
    }

    protected function normalizeNumericValue(int|float|string|null $value): int|float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0;
    }

    /**
     * Sheet 2: Detail Data — seluruh baris hasil filter, sama seperti tabel "Detail Data" pada dashboard.
     */
    protected function buildDetailSheet(Spreadsheet $spreadsheet, Collection $rows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detail Data');

        $headers = [
            'Tanggal Scan', 'Row/Lokasi', 'Customer', 'Code Item', 'Part No.', 'Part Name', 'Model',
            'Barcode', 'NIK Scan', 'PIC Scan', 'Outgoing No', 'Customer To', 'Outgoing Type', 'Qty', 'Unit',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:O1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:O1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        $rowIndex = 2;
        foreach ($rows as $item) {
            $sheet->fromArray([
                $item->scan_date?->format('d-m-Y H:i:s'),
                $item->row_location,
                $item->customer_name,
                $item->code_item,
                $item->part_no,
                $item->part_name,
                $item->model,
                $item->barcode,
                $item->scan_by_nik,
                $item->scan_by_name,
                $item->outgoing_no,
                $item->customer_to,
                $item->outgoing_type,
                $item->qty,
                $item->unit,
            ], null, "A{$rowIndex}");

            $sheet->setCellValueExplicit("N{$rowIndex}", $this->normalizeNumericValue($item->qty), DataType::TYPE_NUMERIC);

            $rowIndex++;
        }

        $lastRow = max($rowIndex - 1, 1);

        $sheet->getStyle("N2:N{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if ($lastRow >= 1) {
            $sheet->getStyle("A1:O{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
    }
}
