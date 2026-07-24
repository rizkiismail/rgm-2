<?php

namespace App\Http\Controllers;

use App\Models\ScanOut;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScanOutController extends Controller
{
    public function index(Request $request)
    {
        ['baseQuery' => $baseQuery, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'bounds' => $bounds] = $this->applyFilters($request);

        // ===================== KARTU RINGKASAN =====================
        $summary = $this->buildSummary($baseQuery);
        $totalScanOut = $summary['totalScanOut'];
        $totalCustomer = $summary['totalCustomer'];
        $totalCodeItem = $summary['totalCodeItem'];
        $totalBarcode = $summary['totalBarcode'];
        $totalOutgoing = $summary['totalOutgoing'];
        $totalPic = $summary['totalPic'];
        $totalQty = $summary['totalQty'];
        $outgoingTypeCount = $summary['outgoingTypeCount'];

        // ===================== GRAFIK TREN (Harian/Bulanan/Tahunan) =====================
        $chartData = [
            'day' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(scan_date, '%Y-%m-%d')"),
            'month' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(scan_date, '%Y-%m')"),
            'year' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(scan_date, '%Y')"),
        ];

        // ===================== PIE CHART: OUTGOING TYPE =====================
        $outgoingTypeChartData = [
            'labels' => array_keys($outgoingTypeCount),
            'values' => array_values($outgoingTypeCount),
        ];

        // ===================== SCAN OUT PER PIC =====================
        $picScan = (clone $baseQuery)
            ->whereNotNull('scan_by_name')
            ->select(
                'scan_by_name',
                DB::raw('MIN(scan_by_nik) as scan_by_nik'),
                DB::raw('COUNT(*) as jumlah_scan'),
                DB::raw('COUNT(DISTINCT barcode) as jumlah_barcode'),
                DB::raw('COALESCE(SUM(qty), 0) as total_qty')
            )
            ->groupBy('scan_by_name')
            ->orderByDesc('jumlah_scan')
            ->orderBy('scan_by_name')
            ->get();

        $picScanChartData = [
            'labels' => $picScan->pluck('scan_by_name')->all(),
            'values' => $picScan->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
        ];

        // ===================== SCAN OUT BERDASARKAN LINE (PIE CHART) =====================
        // Line didapat dengan mencocokkan row_location terhadap tabel master `row_locations`
        // (hasil import Row_Location.xlsx), sama seperti Customer dicocokkan ke Line lewat
        // tabel `customers`. Baris tanpa Row Location yang cocok masuk ke kelompok "Belum Terpetakan".
        $lineBreakdown = (clone $baseQuery)
            ->leftJoin('row_locations', 'row_locations.row_location', '=', 'scan_outs.row_location')
            ->select(
                DB::raw("COALESCE(row_locations.line_label, 'Belum Terpetakan') as line_label"),
                DB::raw('COALESCE(row_locations.line, 99) as line_sort'),
                DB::raw('COUNT(*) as jumlah_scan'),
                DB::raw('COUNT(DISTINCT scan_outs.barcode) as jumlah_barcode'),
                DB::raw('COALESCE(SUM(scan_outs.qty), 0) as total_qty')
            )
            ->groupBy('line_label', 'line_sort')
            ->orderBy('line_sort')
            ->get();

        $lineChartData = [
            'labels' => $lineBreakdown->pluck('line_label')->all(),
            'values' => $lineBreakdown->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
        ];

        // ===================== TOP 10 CUSTOMER & CODE ITEM =====================
        $topCustomers = (clone $baseQuery)
            ->whereNotNull('customer_name')
            ->select(
                'customer_name',
                DB::raw('COUNT(*) as jumlah_scan'),
                DB::raw('COUNT(DISTINCT barcode) as jumlah_barcode'),
                DB::raw('COUNT(DISTINCT code_item) as jumlah_code_item'),
                DB::raw('COALESCE(SUM(qty), 0) as total_qty')
            )
            ->groupBy('customer_name')
            ->orderByDesc('jumlah_scan')
            ->orderBy('customer_name')
            ->get();

        $topCodeItems = (clone $baseQuery)
            ->whereNotNull('code_item')
            ->select(
                'code_item',
                DB::raw('MIN(customer_name) as customer_name'),
                DB::raw('MIN(part_name) as part_name'),
                DB::raw('COUNT(*) as jumlah_scan'),
                DB::raw('COUNT(DISTINCT barcode) as jumlah_barcode'),
                DB::raw('COALESCE(SUM(qty), 0) as total_qty')
            )
            ->groupBy('code_item')
            ->orderByDesc('jumlah_scan')
            ->orderBy('code_item')
            ->get();

        $topCustomerChartData = [
            'labels' => $topCustomers->pluck('customer_name')->all(),
            'values' => $topCustomers->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
        ];
        $topCodeItemChartData = [
            'labels' => $topCodeItems->pluck('code_item')->all(),
            'values' => $topCodeItems->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
        ];

        // Daftar dropdown filter (dari seluruh data, bukan hasil filter).
        $customerOptions = ScanOut::whereNotNull('customer_name')->distinct()->orderBy('customer_name')->pluck('customer_name');
        $outgoingTypeOptions = ScanOut::whereNotNull('outgoing_type')->distinct()->orderBy('outgoing_type')->pluck('outgoing_type');
        $picOptions = ScanOut::whereNotNull('scan_by_name')->distinct()->orderBy('scan_by_name')->pluck('scan_by_name');
        $lineOptions = DB::table('row_locations')->whereNotNull('line_label')->distinct()->orderBy('line')->pluck('line_label');

        // Tabel detail dengan pencarian sederhana + pagination.
        $rows = (clone $baseQuery)->with('rowLocationMaster')->orderByDesc('scan_date')->paginate(25)->withQueryString();

        $exportUrl = route('scanout.export', array_filter([
            'date_from' => $dateFrom?->format('Y-m-d'),
            'date_to' => $dateTo?->format('Y-m-d'),
            'customer' => $request->customer,
            'outgoing_type' => $request->outgoing_type,
            'pic' => $request->pic,
            'line' => $request->line,
            'q' => $request->q,
        ]));

        return view('scanout.dashboard', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'boundsMin' => $bounds->min_date,
            'boundsMax' => $bounds->max_date,
            'totalScanOut' => $totalScanOut,
            'totalCustomer' => $totalCustomer,
            'totalCodeItem' => $totalCodeItem,
            'totalBarcode' => $totalBarcode,
            'totalOutgoing' => $totalOutgoing,
            'totalPic' => $totalPic,
            'totalQty' => $totalQty,
            'outgoingTypeCount' => $outgoingTypeCount,
            'chartData' => $chartData,
            'outgoingTypeChartData' => $outgoingTypeChartData,
            'picScan' => $picScan,
            'picScanChartData' => $picScanChartData,
            'lineChartData' => $lineChartData,
            'topCustomers' => $topCustomers,
            'topCodeItems' => $topCodeItems,
            'topCustomerChartData' => $topCustomerChartData,
            'topCodeItemChartData' => $topCodeItemChartData,
            'customerOptions' => $customerOptions,
            'outgoingTypeOptions' => $outgoingTypeOptions,
            'picOptions' => $picOptions,
            'lineOptions' => $lineOptions,
            'rows' => $rows,
            'filters' => $request->only(['date_from', 'date_to', 'customer', 'outgoing_type', 'pic', 'line', 'q']),
            'hasData' => ScanOut::query()->exists(),
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

        $rows = (clone $baseQuery)->orderByDesc('scan_date')->get();

        $filtersMeta = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'customer' => $request->customer,
            'outgoing_type' => $request->outgoing_type,
            'pic' => $request->pic,
            'line' => $request->line,
            'q' => $request->q,
        ];

        $exporter = new \App\Services\ScanOutExcelExporter();

        return $exporter->export($summary, $rows, $filtersMeta);
    }

    /**
     * Terapkan filter dari request (tanggal, customer, outgoing type, PIC, pencarian)
     * ke query ScanOut. Dipakai bersama oleh index() dan export() supaya
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
            'outgoing_type' => ['nullable', 'string'],
            'pic' => ['nullable', 'string'],
            'line' => ['nullable', 'string'],
            'q' => ['nullable', 'string'],
        ]);

        // Rentang data yang tersedia di database, dipakai sebagai batas default.
        $bounds = ScanOut::selectRaw('MIN(scan_date) as min_date, MAX(scan_date) as max_date')->first();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : ($bounds->min_date ? Carbon::parse($bounds->min_date)->startOfDay() : null);

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : ($bounds->max_date ? Carbon::parse($bounds->max_date)->endOfDay() : null);

        $baseQuery = ScanOut::query();

        if ($dateFrom) {
            $baseQuery->where('scan_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->where('scan_date', '<=', $dateTo);
        }
        if ($request->filled('customer')) {
            $baseQuery->where('customer_name', $request->customer);
        }
        if ($request->filled('outgoing_type')) {
            $baseQuery->where('outgoing_type', $request->outgoing_type);
        }
        if ($request->filled('pic')) {
            $baseQuery->where('scan_by_name', $request->pic);
        }
        if ($request->filled('line')) {
            $line = $request->line;
            $baseQuery->whereIn('scan_outs.row_location', function ($sub) use ($line) {
                $sub->select('row_location')->from('row_locations')->where('line_label', $line);
            });
        }
        if ($request->filled('q')) {
            $keyword = $request->q;
            $baseQuery->where(function ($sub) use ($keyword) {
                $sub->where('code_item', 'like', "%{$keyword}%")
                    ->orWhere('part_name', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%")
                    ->orWhere('outgoing_no', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('scan_by_name', 'like', "%{$keyword}%");
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
     *     totalScanOut:int, totalCustomer:int, totalCodeItem:int, totalBarcode:int,
     *     totalOutgoing:int, totalPic:int, totalQty:float, outgoingTypeCount:array<string,int>
     * }
     */
    protected function buildSummary($baseQuery): array
    {
        return [
            'totalScanOut' => (clone $baseQuery)->count(),
            'totalCustomer' => (clone $baseQuery)->whereNotNull('customer_name')->distinct('customer_name')->count('customer_name'),
            'totalCodeItem' => (clone $baseQuery)->whereNotNull('code_item')->distinct('code_item')->count('code_item'),
            'totalBarcode' => (clone $baseQuery)->whereNotNull('barcode')->distinct('barcode')->count('barcode'),
            'totalOutgoing' => (clone $baseQuery)->whereNotNull('outgoing_no')->distinct('outgoing_no')->count('outgoing_no'),
            'totalPic' => (clone $baseQuery)->whereNotNull('scan_by_name')->distinct('scan_by_name')->count('scan_by_name'),
            'totalQty' => (clone $baseQuery)->sum('qty'),
            'outgoingTypeCount' => $this->outgoingTypeBreakdown($baseQuery),
        ];
    }

    /**
     * Hitung jumlah outgoing (No. Outgoing unik) untuk setiap Outgoing Type.
     *
     * @return array<string, int>
     */
    protected function outgoingTypeBreakdown($baseQuery): array
    {
        $result = (clone $baseQuery)
            ->whereNotNull('outgoing_type')
            ->whereNotNull('outgoing_no')
            ->select('outgoing_type', DB::raw('COUNT(DISTINCT outgoing_no) as jumlah'))
            ->groupBy('outgoing_type')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'outgoing_type');

        return $result->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Hitung tren jumlah Scan Out, Customer (unik), dan Code Item (unik)
     * dikelompokkan berdasarkan ekspresi periode (harian/bulanan/tahunan).
     */
    protected function buildChartSeries($baseQuery, string $periodExpr): array
    {
        $result = (clone $baseQuery)
            ->whereNotNull('scan_date')
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw('COUNT(*) as scan_count')
            ->selectRaw('COUNT(DISTINCT customer_name) as customer_count')
            ->selectRaw('COUNT(DISTINCT code_item) as code_item_count')
            ->selectRaw('COUNT(DISTINCT barcode) as barcode_count')
            ->selectRaw('COALESCE(SUM(qty), 0) as qty_sum')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $result->pluck('period')->all(),
            'scan_out' => $result->pluck('scan_count')->map(fn ($v) => (int) $v)->all(),
            'customer' => $result->pluck('customer_count')->map(fn ($v) => (int) $v)->all(),
            'code_item' => $result->pluck('code_item_count')->map(fn ($v) => (int) $v)->all(),
            'barcode' => $result->pluck('barcode_count')->map(fn ($v) => (int) $v)->all(),
            'qty' => $result->pluck('qty_sum')->map(fn ($v) => (float) $v)->all(),
        ];
    }
}
