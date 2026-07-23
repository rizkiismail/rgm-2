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

class BalanceReturExcelExporter
{
    protected const HEADER_FILL = '1F4E78';

    protected const SECTION_FILL = 'D9E2F3';

    /**
     * Build dan stream file Excel berisi Ringkasan (Summary Cards) dan tabel Detail Data
     * sesuai filter yang sedang aktif.
     *
     * @param  array  $summary  Hasil dari BalanceReturController::buildSummary()
     * @param  Collection  $rows  Seluruh baris BalanceRetur hasil filter (tidak dipaginasi)
     * @param  array  $filtersMeta  ['date_from' => ?Carbon, 'date_to' => ?Carbon, 'customer' => ?string, 'final_status' => ?string, 'q' => ?string]
     */
    public function export(array $summary, Collection $rows, array $filtersMeta): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->buildRingkasanSheet($spreadsheet, $summary, $filtersMeta, $rows->count());
        $this->buildDetailSheet($spreadsheet, $rows);

        // Sheet aktif saat file dibuka adalah Ringkasan.
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Balance_Retur_'.now()->format('Ymd_His').'.xlsx';

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

        // ---- Judul ----
        $sheet->setCellValue("A{$row}", 'MONITORING BALANCE RETUR');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row++;

       
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(11)->getColor()->setRGB('6C757D');
        $row += 2;

        // ---- Info filter ----
        $periode = ($filtersMeta['date_from'] ? Carbon::parse($filtersMeta['date_from'])->format('d-m-Y') : '-')
            .' s/d '.
            ($filtersMeta['date_to'] ? Carbon::parse($filtersMeta['date_to'])->format('d-m-Y') : '-');

        $filterRows = [
            'Periode' => $periode,
            'Customer' => $filtersMeta['customer'] ?: 'Semua Customer',
            'Final Status' => $filtersMeta['final_status'] ?: 'Semua Status',
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

        // ---- Section: Ringkasan Umum ----
        $row = $this->writeSectionHeader($sheet, $row, 'RINGKASAN UMUM');
        $row = $this->writeMetricRow($sheet, $row, 'Total Retur (No. Retur Unik)', $summary['totalRetur']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Customer (Unik)', $summary['totalCustomer']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Code Item (Unik)', $summary['totalCodeItem']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Qty Retur', (float) $summary['totalQtyRetur']);
        $row = $this->writeMetricRow($sheet, $row, 'Total Baris Data', $summary['totalRows']);
        $row++;

        // ---- Section: Receiving Part ----
        $row = $this->writeSectionHeader($sheet, $row, 'QTY RECEIVING PART');
        $row = $this->writeMetricRow($sheet, $row, 'Total Qty Receiving Part', (float) $summary['totalQtyReceivingPart']);
        $row = $this->writeMetricRow($sheet, $row, 'Qty Pending Receiving Part', (float) $summary['totalQtyPendingReceivingPart']);
        $row = $this->writeMetricRow($sheet, $row, 'Status Receiving: CLOSE', $summary['receivingStatusCount']['CLOSE']);
        $row = $this->writeMetricRow($sheet, $row, 'Status Receiving: OPEN', $summary['receivingStatusCount']['OPEN']);
        $row++;

        // ---- Section: Delivery Part ----
        $row = $this->writeSectionHeader($sheet, $row, 'QTY DELIVERY PART');
        $row = $this->writeMetricRow($sheet, $row, 'Total Qty Delivery Part', (float) $summary['totalQtyDeliveryPart']);
        $row = $this->writeMetricRow($sheet, $row, 'Qty Pending Delivery Part', (float) $summary['totalQtyPendingDeliveryPart']);
        $row = $this->writeMetricRow($sheet, $row, 'Status Delivery: CLOSE', $summary['deliveryStatusCount']['CLOSE']);
        $row = $this->writeMetricRow($sheet, $row, 'Status Delivery: OPEN', $summary['deliveryStatusCount']['OPEN']);
        $row++;

        // ---- Section: Final Status ----
        $row = $this->writeSectionHeader($sheet, $row, 'FINAL STATUS');
        $row = $this->writeMetricRow($sheet, $row, 'Final Status: CLOSE', $summary['finalStatusCount']['CLOSE']);
        $row = $this->writeMetricRow($sheet, $row, 'Final Status: OPEN', $summary['finalStatusCount']['OPEN']);
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
            'Tanggal Retur', 'No. Retur', 'Customer', 'Code Item', 'Part Name',
            'Qty Retur', 'Unit', 'Qty Receiving','Qty Pending Receiving', 'Status Receiving',
            'Qty Delivery','Qty Pending Delivery', 'Status Delivery', 'Final Status', 'Note', 'PIC PPIC Delivery',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:P1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:P1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $sheet->getStyle('A1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        $rowIndex = 2;
        foreach ($rows as $item) {
            $sheet->fromArray([
                $item->date_retur?->format('d-m-Y'),
                $item->no_retur,
                $item->customer_name,
                $item->code_item,
                $item->part_name,
                null,
                $item->unit,
                null,
                null,
                $item->status_receiving,
                null,
                null,
                $item->status_delivery,
                $item->final_status,
                $item->note,
                $item->pic_ppic_delivery,
            ], null, "A{$rowIndex}");

            $sheet->setCellValueExplicit("F{$rowIndex}", $this->normalizeNumericValue($item->qty_retur), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("H{$rowIndex}", $this->normalizeNumericValue($item->qty_receiving_part), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("I{$rowIndex}", $this->normalizeNumericValue($item->qty_pending_receiving_part), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("K{$rowIndex}", $this->normalizeNumericValue($item->qty_delivery_part), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("L{$rowIndex}", $this->normalizeNumericValue($item->qty_pending_delivery_part), DataType::TYPE_NUMERIC);

            $rowIndex++;
        }

        $lastRow = max($rowIndex - 1, 1);

        // Format angka untuk kolom qty.
        foreach (['F', 'H', 'I','K','L'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Lebar kolom otomatis menyesuaikan isi.
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Garis border tipis untuk seluruh tabel supaya rapi saat dicetak.
        if ($lastRow >= 1) {
            $sheet->getStyle("A1:P{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
    }
}
