<?php

namespace App\Http\Controllers;

use App\Models\ScanOut;
use App\Services\ScanOutFacts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScanOutController extends Controller
{
    public function index(Request $request)
    {
        ['baseQuery' => $baseQuery, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'bounds' => $bounds] = $this->applyFilters($request);

        // ===================== AGREGASI DASHBOARD =====================
        // Seluruh angka ringkasan, grafik tren, PIC, Line, Top Customer/Code Item, dan
        // grafik per jam dihitung dari tabel fakta sempit `scan_out_facts` (lihat
        // migration create_scan_out_facts_table), bukan dari tabel scan_outs yang berisi
        // 600rb+ baris teks panjang. Hasilnya di-cache per kombinasi filter + "versi data"
        // (versi dinaikkan otomatis setiap import), jadi buka berikutnya instan.
        $cacheKey = 'scan_out.dashboard.'.Cache::get('scan_out.data_version', '0').'.'.md5(json_encode([
            $dateFrom?->toDateString(),
            $dateTo?->toDateString(),
            $request->input('customer'),
            $request->input('outgoing_type'),
            $request->input('pic'),
            $request->input('line'),
            $request->input('q'),
        ]));

        $aggregates = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            fn () => $this->buildAggregates($request, $dateFrom, $dateTo)
        );
        extract($aggregates);

        // Daftar dropdown filter (dari seluruh data, bukan hasil filter).
        // Di-cache singkat karena query ini tidak bergantung pada filter tapi tadinya
        // dijalankan ulang (full scan) di setiap request.
        $customerOptions = \Illuminate\Support\Facades\Cache::remember(
            'scan_out.customer_options',
            300,
            fn () => ScanOut::whereNotNull('customer_name')->distinct()->orderBy('customer_name')->pluck('customer_name')
        );
        $outgoingTypeOptions = \Illuminate\Support\Facades\Cache::remember(
            'scan_out.outgoing_type_options',
            300,
            fn () => ScanOut::whereNotNull('outgoing_type')->distinct()->orderBy('outgoing_type')->pluck('outgoing_type')
        );
        $picOptions = \Illuminate\Support\Facades\Cache::remember(
            'scan_out.pic_options',
            300,
            fn () => ScanOut::whereNotNull('scan_by_name')->distinct()->orderBy('scan_by_name')->pluck('scan_by_name')
        );
       $lineOptions = \Illuminate\Support\Facades\Cache::remember(
    'scan_out.line_options',
    300,
    fn () => DB::table('row_locations')
        ->whereNotNull('line_label')
        ->groupBy('line_label')
        ->orderBy('line_label')
        ->pluck('line_label')
);

        // Tabel detail dengan pencarian sederhana + pagination.
        // Total baris sudah diketahui dari agregasi ($totalScanOut = jumlah baris sesuai filter yang
        // sama), jadi diberikan langsung ke paginator. Tanpa ini, paginate() menjalankan
        // COUNT(*) tambahan pada tabel scan_outs (dengan subquery Line bisa >10 detik).
        $rows = (clone $baseQuery)->with('rowLocationMaster')->orderByDesc('scan_date')
            ->paginate(25, ['*'], 'page', null, $totalScanOut)
            ->withQueryString();

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
            'hourlyScanChartData' => $hourlyScanChartData,
            'rows' => $rows,
            'filters' => $request->only(['date_from', 'date_to', 'customer', 'outgoing_type', 'pic', 'line', 'q']),
            'hasData' => ScanOut::query()->exists(),
            'exportUrl' => $exportUrl,
        ]);
    }

    /**
     * Hitung jumlah outgoing (No. Outgoing unik) untuk setiap Outgoing Type.
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
        // Satu query tunggal untuk seluruh angka scalar Ringkasan (dulu 6 query
        // terpisah yang masing-masing full scan tabel yang sama).
        $row = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_scan_out')
            ->selectRaw('COUNT(DISTINCT CASE WHEN customer_name IS NOT NULL THEN customer_name END) as total_customer')
            ->selectRaw('COUNT(DISTINCT CASE WHEN code_item IS NOT NULL THEN code_item END) as total_code_item')
            ->selectRaw('COUNT(DISTINCT CASE WHEN barcode IS NOT NULL THEN barcode END) as total_barcode')
            ->selectRaw('COUNT(DISTINCT CASE WHEN outgoing_no IS NOT NULL THEN outgoing_no END) as total_outgoing')
            ->selectRaw('COUNT(DISTINCT CASE WHEN scan_by_name IS NOT NULL THEN scan_by_name END) as total_pic')
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty')
            ->first();

        return [
            'totalScanOut' => (int) $row->total_scan_out,
            'totalCustomer' => (int) $row->total_customer,
            'totalCodeItem' => (int) $row->total_code_item,
            'totalBarcode' => (int) $row->total_barcode,
            'totalOutgoing' => (int) $row->total_outgoing,
            'totalPic' => (int) $row->total_pic,
            'totalQty' => (float) $row->total_qty,
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
     * Hitung semua agregasi dashboard dari tabel fakta. Hasilnya berupa array yang di-extract
     * ke variabel view di index().
     */
    protected function buildAggregates(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $this->ensureFactsReady();

        // Kamus nilai teks: kind => [id => [value, extra]]
        $terms = [];
        foreach (DB::table('scan_out_terms')->get() as $t) {
            $terms[$t->kind][$t->id] = $t;
        }
        $name = fn (int $kind, $id) => $id !== null && isset($terms[$kind][$id]) ? $terms[$kind][$id]->value : null;

        $keyword = $request->filled('q') ? $request->q : null;

        try {
            $facts = $this->factQuery($request, $dateFrom, $dateTo, $keyword);

            // ---------- Kartu ringkasan ----------
            $row = (clone $facts)
                ->selectRaw('COUNT(*) as total_scan_out')
                ->selectRaw('COUNT(DISTINCT f.cust) as total_customer')
                ->selectRaw('COUNT(DISTINCT f.item) as total_code_item')
                ->selectRaw('COUNT(DISTINCT f.bc) as total_barcode')
                ->selectRaw('COUNT(DISTINCT f.outg) as total_outgoing')
                ->selectRaw('COUNT(DISTINCT f.pic) as total_pic')
                ->selectRaw('COALESCE(SUM(f.qty), 0) as total_qty')
                ->first();

            $totalScanOut = (int) $row->total_scan_out;
            $totalCustomer = (int) $row->total_customer;
            $totalCodeItem = (int) $row->total_code_item;
            $totalBarcode = (int) $row->total_barcode;
            $totalOutgoing = (int) $row->total_outgoing;
            $totalPic = (int) $row->total_pic;
            $totalQty = (float) $row->total_qty;

            // Jumlah outgoing (No. Outgoing unik) per Outgoing Type.
            $outgoingTypeCount = [];
            $typeRows = (clone $facts)
                ->whereNotNull('f.typ')
                ->whereNotNull('f.outg')
                ->select('f.typ')
                ->selectRaw('COUNT(DISTINCT f.outg) as jumlah')
                ->groupBy('f.typ')
                ->orderByDesc('jumlah')
                ->get();
            foreach ($typeRows as $r) {
                $label = $name(ScanOutFacts::KIND_TYPE, $r->typ);
                if ($label !== null) {
                    $outgoingTypeCount[$label] = (int) $r->jumlah;
                }
            }

            $outgoingTypeChartData = [
                'labels' => array_keys($outgoingTypeCount),
                'values' => array_values($outgoingTypeCount),
            ];

            // ---------- Grafik tren (harian / bulanan / tahunan) ----------
            $chartData = [
                'day' => $this->buildChartSeries($facts, "DATE_FORMAT(f.d, '%Y-%m-%d')"),
                'month' => $this->buildChartSeries($facts, "DATE_FORMAT(f.d, '%Y-%m')"),
                'year' => $this->buildChartSeries($facts, "DATE_FORMAT(f.d, '%Y')"),
            ];

            // ---------- Scan Out per PIC ----------
            $picScan = (clone $facts)
                ->whereNotNull('f.pic')
                ->select('f.pic')
                ->selectRaw('COUNT(*) as jumlah_scan')
                ->selectRaw('COUNT(DISTINCT f.bc) as jumlah_barcode')
                ->selectRaw('COALESCE(SUM(f.qty), 0) as total_qty')
                ->groupBy('f.pic')
                ->get()
                ->map(function ($r) use ($terms) {
                    $term = $terms[ScanOutFacts::KIND_PIC][$r->pic] ?? null;

                    return (object) [
                        'scan_by_name' => $term?->value,
                        'scan_by_nik' => $term?->extra,
                        'jumlah_scan' => (int) $r->jumlah_scan,
                        'jumlah_barcode' => (int) $r->jumlah_barcode,
                        'total_qty' => $r->total_qty,
                    ];
                });
            $picScan = $this->sortByCountThenName($picScan, 'jumlah_scan', 'scan_by_name');

            $picScanChartData = [
                'labels' => $picScan->pluck('scan_by_name')->all(),
                'values' => $picScan->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
            ];

            // ---------- Scan Out per Line (pie chart) ----------
            // Line dipetakan dari row_location lewat master `row_locations` saat tabel fakta
            // dibangun; baris tanpa pemetaan masuk "Belum Terpetakan".
            $lineBreakdown = (clone $facts)
                ->select('f.line_term', 'f.line_sort')
                ->selectRaw('COUNT(*) as jumlah_scan')
                ->groupBy('f.line_term', 'f.line_sort')
                ->orderBy('f.line_sort')
                ->get();

            $lineChartData = [
                'labels' => $lineBreakdown->map(fn ($r) => $name(ScanOutFacts::KIND_LINE, $r->line_term) ?? 'Belum Terpetakan')->all(),
                'values' => $lineBreakdown->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
            ];

            // ---------- Top Customer & Code Item ----------
            $topCustomers = (clone $facts)
                ->whereNotNull('f.cust')
                ->select('f.cust')
                ->selectRaw('COUNT(*) as jumlah_scan')
                ->selectRaw('COUNT(DISTINCT f.bc) as jumlah_barcode')
                ->selectRaw('COUNT(DISTINCT f.item) as jumlah_code_item')
                ->selectRaw('COALESCE(SUM(f.qty), 0) as total_qty')
                ->groupBy('f.cust')
                ->get()
                ->map(fn ($r) => (object) [
                    'customer_name' => $name(ScanOutFacts::KIND_CUSTOMER, $r->cust),
                    'jumlah_scan' => (int) $r->jumlah_scan,
                    'jumlah_barcode' => (int) $r->jumlah_barcode,
                    'jumlah_code_item' => (int) $r->jumlah_code_item,
                    'total_qty' => $r->total_qty,
                ]);
            $topCustomers = $this->sortByCountThenName($topCustomers, 'jumlah_scan', 'customer_name');

            $topCodeItems = (clone $facts)
                ->whereNotNull('f.item')
                ->leftJoin('scan_out_terms as tc', 'tc.id', '=', 'f.cust')
                ->select('f.item')
                ->selectRaw('MIN(tc.value) as customer_name')
                ->selectRaw('COUNT(*) as jumlah_scan')
                ->selectRaw('COUNT(DISTINCT f.bc) as jumlah_barcode')
                ->selectRaw('COALESCE(SUM(f.qty), 0) as total_qty')
                ->groupBy('f.item')
                ->get()
                ->map(function ($r) use ($terms) {
                    $term = $terms[ScanOutFacts::KIND_ITEM][$r->item] ?? null;

                    return (object) [
                        'code_item' => $term?->value,
                        'customer_name' => $r->customer_name,
                        'part_name' => $term?->extra,
                        'jumlah_scan' => (int) $r->jumlah_scan,
                        'jumlah_barcode' => (int) $r->jumlah_barcode,
                        'total_qty' => $r->total_qty,
                    ];
                });
            $topCodeItems = $this->sortByCountThenName($topCodeItems, 'jumlah_scan', 'code_item');

            $topCustomerChartData = [
                'labels' => $topCustomers->pluck('customer_name')->all(),
                'values' => $topCustomers->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
            ];
            $topCodeItemChartData = [
                'labels' => $topCodeItems->pluck('code_item')->all(),
                'values' => $topCodeItems->pluck('jumlah_scan')->map(fn ($v) => (int) $v)->all(),
            ];

            // ---------- Jumlah scan per jam (00:00 - 23:00) ----------
            $hours = (clone $facts)
                ->select('f.h as hour')
                ->selectRaw('COUNT(*) as scan_count')
                ->groupBy('f.h')
                ->get()
                ->keyBy('hour');

            $hourlyScanChartData = ['labels' => [], 'values' => []];
            for ($hour = 0; $hour < 24; $hour++) {
                $hourRow = $hours->get($hour);
                $hourlyScanChartData['labels'][] = sprintf('%02d:00', $hour);
                $hourlyScanChartData['values'][] = $hourRow ? (int) $hourRow->scan_count : 0;
            }
        } finally {
            if ($keyword !== null) {
                DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_scan_out_q');
            }
        }

        return compact(
            'totalScanOut', 'totalCustomer', 'totalCodeItem', 'totalBarcode', 'totalOutgoing',
            'totalPic', 'totalQty', 'outgoingTypeCount', 'outgoingTypeChartData', 'chartData',
            'picScan', 'picScanChartData', 'lineChartData', 'topCustomers', 'topCodeItems',
            'topCustomerChartData', 'topCodeItemChartData', 'hourlyScanChartData'
        );
    }

    /**
     * Query dasar pada tabel fakta dengan filter yang setara dengan applyFilters().
     */
    protected function factQuery(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo, ?string $keyword)
    {
        $facts = DB::table('scan_out_facts as f');

        if ($dateFrom) {
            $facts->where('f.d', '>=', $dateFrom->toDateString());
        }
        if ($dateTo) {
            $facts->where('f.d', '<=', $dateTo->toDateString());
        }

        // Filter nilai teks -> ID kamus. Nilai yang tidak ada di kamus = tidak ada hasil.
        $termFilters = [
            ['customer', ScanOutFacts::KIND_CUSTOMER, 'f.cust'],
            ['outgoing_type', ScanOutFacts::KIND_TYPE, 'f.typ'],
            ['pic', ScanOutFacts::KIND_PIC, 'f.pic'],
            ['line', ScanOutFacts::KIND_LINE, 'f.line_term'],
        ];
        foreach ($termFilters as [$param, $kind, $column]) {
            if (! $request->filled($param)) {
                continue;
            }
            $id = DB::table('scan_out_terms')->where('kind', $kind)->where('value', $request->input($param))->value('id');
            if ($id === null) {
                $facts->whereRaw('1 = 0');
            } else {
                $facts->where($column, $id);
            }
        }

        // Pencarian teks bebas (code item, part name, barcode, no outgoing, customer, PIC)
        // butuh kolom teks di tabel scan_outs. Cukup dicari SEKALI ke tabel temporary berisi
        // ID baris yang cocok, lalu di-join ke tabel fakta oleh semua query agregasi.
        if ($keyword !== null) {
            $matches = DB::table('scan_outs')->select('id')->where(function ($sub) use ($keyword) {
                $sub->where('code_item', 'like', "%{$keyword}%")
                    ->orWhere('part_name', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%")
                    ->orWhere('outgoing_no', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('scan_by_name', 'like', "%{$keyword}%");
            });

            DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_scan_out_q');
            DB::statement(
                'CREATE TEMPORARY TABLE tmp_scan_out_q (PRIMARY KEY (id)) AS '.$matches->toSql(),
                $matches->getBindings()
            );
            $facts->join('tmp_scan_out_q as q', 'q.id', '=', 'f.id');
        }

        return $facts;
    }

    /**
     * Tren jumlah Scan Out, Customer (unik), Code Item (unik), Barcode (unik) dan Qty per
     * periode ($periodExpr = ekspresi SQL atas kolom f.d).
     */
    protected function buildChartSeries($facts, string $periodExpr): array
    {
        $result = (clone $facts)
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw('COUNT(*) as scan_count')
            ->selectRaw('COUNT(DISTINCT f.cust) as customer_count')
            ->selectRaw('COUNT(DISTINCT f.item) as code_item_count')
            ->selectRaw('COUNT(DISTINCT f.bc) as barcode_count')
            ->selectRaw('COALESCE(SUM(f.qty), 0) as qty_sum')
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

    /**
     * Urutkan menurun berdasarkan jumlah, lalu menaik berdasarkan nama (tanpa beda huruf besar/kecil).
     */
    protected function sortByCountThenName($collection, string $countKey, string $nameKey)
    {
        return $collection->sort(function ($a, $b) use ($countKey, $nameKey) {
            return [$b->{$countKey}, strtolower((string) $a->{$nameKey})]
                <=> [$a->{$countKey}, strtolower((string) $b->{$nameKey})];
        })->values();
    }

    /**
     * Pastikan tabel fakta sudah ada dan terisi; kalau belum, beri pesan yang jelas.
     */
    protected function ensureFactsReady(): void
    {
        $missing = ! Schema::hasTable('scan_out_facts');
        $empty = ! $missing
            && ! DB::table('scan_out_facts')->exists()
            && ScanOut::query()->exists();

        if ($missing || $empty) {
            throw new \RuntimeException(
                'Tabel fakta dashboard Scan Out belum dibuat/terisi. Jalankan di terminal: '.
                'php artisan migrate  lalu  php artisan scanout:rebuild'
            );
        }
    }
}
