<?php

namespace App\Http\Controllers;

use App\Models\BalanceRetur;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceReturController extends Controller
{
    public function index(Request $request)
    {
        ['baseQuery' => $baseQuery, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'bounds' => $bounds] = $this->applyFilters($request);

        // ===================== KARTU RINGKASAN =====================
        $summary = $this->buildSummary($baseQuery);
        $totalRetur = $summary['totalRetur'];
        $totalCustomer = $summary['totalCustomer'];
        $totalCodeItem = $summary['totalCodeItem'];
        $totalRows = $summary['totalRows'];
        $totalQtyRetur = $summary['totalQtyRetur'];
        $totalQtyReceivingPart = $summary['totalQtyReceivingPart'];
        $totalQtyPendingReceivingPart = $summary['totalQtyPendingReceivingPart'];
        $receivingStatusCount = $summary['receivingStatusCount'];
        $totalQtyDeliveryPart = $summary['totalQtyDeliveryPart'];
        $totalQtyPendingDeliveryPart = $summary['totalQtyPendingDeliveryPart'];
        $deliveryStatusCount = $summary['deliveryStatusCount'];
        $finalStatusCount = $summary['finalStatusCount'];

        // ===================== GRAFIK TREN (Harian/Bulanan/Tahunan) =====================
        $chartData = [
            'day' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(date_retur, '%Y-%m-%d')"),
            'month' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(date_retur, '%Y-%m')"),
            'year' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(date_retur, '%Y')"),
        ];

        // ===================== GRAFIK STATUS (doughnut) =====================
        $statusChartData = [
            'receiving' => ['labels' => ['CLOSE', 'OPEN'], 'values' => [$receivingStatusCount['CLOSE'], $receivingStatusCount['OPEN']]],
            'delivery' => ['labels' => ['CLOSE', 'OPEN'], 'values' => [$deliveryStatusCount['CLOSE'], $deliveryStatusCount['OPEN']]],
            'final' => ['labels' => ['CLOSE', 'OPEN'], 'values' => [$finalStatusCount['CLOSE'], $finalStatusCount['OPEN']]],
        ];

        // ===================== TOP 10 CUSTOMER & CODE ITEM =====================
        $topCustomers = (clone $baseQuery)
            ->whereNotNull('customer_name')
            ->select(
                'customer_name',
                DB::raw('COUNT(DISTINCT no_retur) as jumlah_retur'),
                DB::raw('COALESCE(SUM(qty_retur), 0) as total_qty_retur'),
                DB::raw('COALESCE(SUM(qty_receiving_part), 0) as total_qty_receiving'),
                DB::raw('COALESCE(SUM(qty_delivery_part), 0) as total_qty_delivery'),
                DB::raw("SUM(CASE WHEN final_status = 'OPEN' THEN 1 ELSE 0 END) as open_count")
            )
            ->groupBy('customer_name')
            ->orderByDesc('total_qty_retur')
            ->orderBy('customer_name')
            ->get();

        $topCodeItems = (clone $baseQuery)
            ->whereNotNull('code_item')
            ->select(
                'code_item',
                DB::raw('MIN(customer_name) as customer_name'),
                DB::raw('MIN(part_name) as part_name'),
                DB::raw('COUNT(DISTINCT no_retur) as jumlah_retur'),
                DB::raw('COALESCE(SUM(qty_retur), 0) as total_qty_retur'),
                DB::raw("SUM(CASE WHEN final_status = 'OPEN' THEN 1 ELSE 0 END) as open_count")
            )
            ->groupBy('code_item')
            ->orderByDesc('total_qty_retur')
            ->orderBy('code_item')
            ->get();

        $topPicDelivery = (clone $baseQuery)
            ->whereNotNull('pic_ppic_delivery')
            ->select(
                'pic_ppic_delivery',
                DB::raw('COUNT(DISTINCT no_retur) as jumlah_retur'),
                DB::raw('COALESCE(SUM(qty_retur), 0) as total_qty_retur'),
                DB::raw('COALESCE(SUM(qty_delivery_part), 0) as total_qty_delivery'),
                DB::raw("SUM(CASE WHEN status_delivery = 'CLOSE' THEN 1 ELSE 0 END) as close_count"),
                DB::raw("SUM(CASE WHEN status_delivery = 'OPEN' THEN 1 ELSE 0 END) as open_count")
            )
            ->groupBy('pic_ppic_delivery')
            ->orderByDesc('total_qty_delivery')
            ->orderBy('pic_ppic_delivery')
            ->get();

        // Data untuk bar chart top 10 (dari query yang sama, tanpa query ulang).
        $topCustomerChartData = [
            'labels' => $topCustomers->pluck('customer_name')->all(),
            'values' => $topCustomers->pluck('total_qty_retur')->map(fn ($v) => (float) $v)->all(),
        ];
        $topCodeItemChartData = [
            'labels' => $topCodeItems->pluck('code_item')->all(),
            'values' => $topCodeItems->pluck('total_qty_retur')->map(fn ($v) => (float) $v)->all(),
        ];
        $topPicDeliveryChartData = [
            'labels' => $topPicDelivery->pluck('pic_ppic_delivery')->all(),
            'values' => $topPicDelivery->pluck('total_qty_delivery')->map(fn ($v) => (float) $v)->all(),
        ];

        // Daftar customer untuk dropdown filter (dari seluruh data, bukan hasil filter).
        $customerOptions = BalanceRetur::whereNotNull('customer_name')->distinct()->orderBy('customer_name')->pluck('customer_name');

        // Tabel detail dengan pencarian sederhana + pagination.
        $rows = (clone $baseQuery)->orderByDesc('date_retur')->paginate(25)->withQueryString();

        // URL export Excel yang membawa filter (tanggal efektif + filter lain) yang sedang aktif.
        $exportUrl = route('retur.export', array_filter([
            'date_from' => $dateFrom?->format('Y-m-d'),
            'date_to' => $dateTo?->format('Y-m-d'),
            'customer' => $request->customer,
            'final_status' => $request->final_status,
            'q' => $request->q,
        ]));

        return view('retur.dashboard', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'boundsMin' => $bounds->min_date,
            'boundsMax' => $bounds->max_date,
            'totalRetur' => $totalRetur,
            'totalCustomer' => $totalCustomer,
            'totalCodeItem' => $totalCodeItem,
            'totalRows' => $totalRows,
            'totalQtyRetur' => $totalQtyRetur,
            'totalQtyReceivingPart' => $totalQtyReceivingPart,
            'totalQtyPendingReceivingPart' => $totalQtyPendingReceivingPart,
            'receivingStatusCount' => $receivingStatusCount,
            'totalQtyDeliveryPart' => $totalQtyDeliveryPart,
            'totalQtyPendingDeliveryPart' => $totalQtyPendingDeliveryPart,
            'deliveryStatusCount' => $deliveryStatusCount,
            'finalStatusCount' => $finalStatusCount,
            'chartData' => $chartData,
            'statusChartData' => $statusChartData,
            'topCustomers' => $topCustomers,
            'topCodeItems' => $topCodeItems,
            'topPicDelivery' => $topPicDelivery,
            'topCustomerChartData' => $topCustomerChartData,
            'topCodeItemChartData' => $topCodeItemChartData,
            'topPicDeliveryChartData' => $topPicDeliveryChartData,
            'customerOptions' => $customerOptions,
            'rows' => $rows,
            'filters' => $request->only(['date_from', 'date_to', 'customer', 'final_status', 'q']),
            'hasData' => BalanceRetur::query()->exists(),
            'exportUrl' => $exportUrl,
        ]);
    }

    /**
     * Export Ringkasan (Summary Cards) beserta tabel Detail Data ke file Excel,
     * mengikuti filter yang sedang aktif pada dashboard (tanpa pagination).
     */
    public function export(Request $request)
    {
        ['baseQuery' => $baseQuery, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo] = $this->applyFilters($request);

        $summary = $this->buildSummary($baseQuery);

        $rows = (clone $baseQuery)->orderByDesc('date_retur')->get();

        $filtersMeta = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'customer' => $request->customer,
            'final_status' => $request->final_status,
            'q' => $request->q,
        ];

        $exporter = new \App\Services\BalanceReturExcelExporter();

        return $exporter->export($summary, $rows, $filtersMeta);
    }

    /**
     * Terapkan filter dari request (tanggal, customer, final status, pencarian)
     * ke query BalanceRetur. Dipakai bersama oleh index() dan export() supaya
     * hasil yang ditampilkan dan yang diexport selalu konsisten.
     *
     * @return array{baseQuery: \Illuminate\Database\Eloquent\Builder, dateFrom: ?Carbon, dateTo: ?Carbon, bounds: object}
     */
    protected function applyFilters(Request $request): array
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'customer' => ['nullable', 'string'],
            'final_status' => ['nullable', 'in:CLOSE,OPEN'],
            'q' => ['nullable', 'string'],
        ]);

        // Rentang data yang tersedia di database, dipakai sebagai batas default.
        $bounds = BalanceRetur::selectRaw('MIN(date_retur) as min_date, MAX(date_retur) as max_date')->first();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : ($bounds->min_date ? Carbon::parse($bounds->min_date)->startOfDay() : null);

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : ($bounds->max_date ? Carbon::parse($bounds->max_date)->endOfDay() : null);

        $baseQuery = BalanceRetur::query();

        if ($dateFrom) {
            $baseQuery->where('date_retur', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->where('date_retur', '<=', $dateTo);
        }
        if ($request->filled('customer')) {
            $baseQuery->where('customer_name', $request->customer);
        }
        if ($request->filled('final_status')) {
            $baseQuery->where('final_status', $request->final_status);
        }
        if ($request->filled('q')) {
            $keyword = $request->q;
            $baseQuery->where(function ($sub) use ($keyword) {
                $sub->where('code_item', 'like', "%{$keyword}%")
                    ->orWhere('part_name', 'like', "%{$keyword}%")
                    ->orWhere('no_retur', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('pic_ppic_delivery', 'like', "%{$keyword}%");
            });
        }

        return [
            'baseQuery' => $baseQuery,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'bounds' => $bounds,
        ];
    }

    /**
     * Hitung seluruh metrik Ringkasan (Summary Cards) dari query yang sudah difilter.
     * Dipakai bersama oleh index() (tampilan) dan export() (Excel) supaya angkanya sama persis.
     *
     * @return array{
     *     totalRetur:int, totalCustomer:int, totalCodeItem:int, totalRows:int, totalQtyRetur:float,
     *     totalQtyReceivingPart:float, totalQtyPendingReceivingPart:float,
     *     receivingStatusCount:array{CLOSE:int,OPEN:int},
     *     totalQtyDeliveryPart:float, totalQtyPendingDeliveryPart:float,
     *     deliveryStatusCount:array{CLOSE:int,OPEN:int},
     *     finalStatusCount:array{CLOSE:int,OPEN:int}
     * }
     */
    protected function buildSummary($baseQuery): array
    {
        return [
            'totalRetur' => (clone $baseQuery)->distinct('no_retur')->count('no_retur'),
            'totalCustomer' => (clone $baseQuery)->whereNotNull('customer_name')->distinct('customer_name')->count('customer_name'),
            'totalCodeItem' => (clone $baseQuery)->whereNotNull('code_item')->distinct('code_item')->count('code_item'),
            'totalRows' => (clone $baseQuery)->count(),
            'totalQtyRetur' => (clone $baseQuery)->sum('qty_retur'),
            'totalQtyReceivingPart' => (clone $baseQuery)->sum('qty_receiving_part'),
            'totalQtyPendingReceivingPart' => (clone $baseQuery)->sum('qty_pending_receiving_part'),
            'receivingStatusCount' => $this->statusBreakdown($baseQuery, 'status_receiving'),
            'totalQtyDeliveryPart' => (clone $baseQuery)->sum('qty_delivery_part'),
            'totalQtyPendingDeliveryPart' => (clone $baseQuery)->sum('qty_pending_delivery_part'),
            'deliveryStatusCount' => $this->statusBreakdown($baseQuery, 'status_delivery'),
            'finalStatusCount' => $this->statusBreakdown($baseQuery, 'final_status'),
        ];
    }

    /**
     * Hitung jumlah baris berstatus CLOSE dan OPEN untuk kolom status tertentu
     * (status_receiving / status_delivery / final_status).
     *
     * @return array{CLOSE:int, OPEN:int}
     */
    protected function statusBreakdown($baseQuery, string $column): array
    {
        $result = (clone $baseQuery)
            ->whereNotNull($column)
            ->select($column, DB::raw('COUNT(*) as jumlah'))
            ->groupBy($column)
            ->pluck('jumlah', $column);

        return [
            'CLOSE' => (int) ($result['CLOSE'] ?? 0),
            'OPEN' => (int) ($result['OPEN'] ?? 0),
        ];
    }

    /**
     * Hitung tren jumlah Retur (unik), Customer (unik), dan Code Item (unik)
     * dikelompokkan berdasarkan ekspresi periode (harian/bulanan/tahunan).
     */
    protected function buildChartSeries($baseQuery, string $periodExpr): array
    {
        $result = (clone $baseQuery)
            ->whereNotNull('date_retur')
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw('COUNT(DISTINCT no_retur) as retur_count')
            ->selectRaw('COUNT(DISTINCT customer_name) as customer_count')
            ->selectRaw('COUNT(DISTINCT code_item) as code_item_count')
            ->selectRaw('COALESCE(SUM(qty_retur), 0) as qty_retur_sum')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $result->pluck('period')->all(),
            'retur' => $result->pluck('retur_count')->map(fn ($v) => (int) $v)->all(),
            'customer' => $result->pluck('customer_count')->map(fn ($v) => (int) $v)->all(),
            'code_item' => $result->pluck('code_item_count')->map(fn ($v) => (int) $v)->all(),
            'qty_retur' => $result->pluck('qty_retur_sum')->map(fn ($v) => (float) $v)->all(),
        ];
    }
}
