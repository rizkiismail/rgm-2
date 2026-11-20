<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ReceivingGood;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'customer' => ['nullable', 'string'],
            'pic' => ['nullable', 'string'],
            'line' => ['nullable', 'integer'],
            'q' => ['nullable', 'string'],
        ]);

        // Rentang data yang tersedia di database, dipakai sebagai batas default.
        $bounds = ReceivingGood::selectRaw('MIN(date_income) as min_date, MAX(date_income) as max_date')->first();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : ($bounds->min_date ? Carbon::parse($bounds->min_date)->startOfDay() : null);

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : ($bounds->max_date ? Carbon::parse($bounds->max_date)->endOfDay() : null);

        $baseQuery = ReceivingGood::query();

        if ($dateFrom) {
            $baseQuery->where('date_income', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->where('date_income', '<=', $dateTo);
        }
        if ($request->filled('customer')) {
            $baseQuery->where('customer', $request->customer);
        }
        if ($request->filled('pic')) {
            $baseQuery->where('verify_by', $request->pic);
        }
        if ($request->filled('line')) {
            $line = $request->integer('line');
            $baseQuery->whereIn('customer', function ($sub) use ($line) {
                $sub->select('name')->from('customers')->where('line', $line);
            });
        }

        // Seluruh agregasi berat (statistik, top customer/item, grafik tren) dihitung
        // sekali lalu di-cache berdasarkan kombinasi filter + "versi data".
        // Versi data dinaikkan otomatis setiap kali ada import (lihat importer),
        // jadi cache tidak pernah menampilkan data basi setelah upload.
        // Tanpa cache, tiap buka halaman = belasan query COUNT(DISTINCT) yang
        // memindai seluruh tabel -> melewati batas 60 detik saat data membesar.
        $cacheKey = 'receiving_goods.dashboard.'.Cache::get('receiving_goods.data_version', '0').'.'.md5(json_encode([
            $dateFrom?->toDateTimeString(),
            $dateTo?->toDateTimeString(),
            $request->input('customer'),
            $request->input('pic'),
            $request->input('line'),
        ]));

        // Query agregasi membaca tabel ringkasan `receiving_goods_rollup` (lihat migration
        // create_receiving_goods_rollup_table), bukan tabel utama yang berisi 600rb+ baris.
        // Filter-nya setara dengan $baseQuery di atas (rentang tanggal, customer, PIC, Line).
        $rollup = DB::table('receiving_goods_rollup');
        if ($dateFrom) {
            $rollup->where('work_date', '>=', $dateFrom->toDateString());
        }
        if ($dateTo) {
            $rollup->where('work_date', '<=', $dateTo->toDateString());
        }
        if ($request->filled('customer')) {
            $rollup->where('customer', $request->customer);
        }
        if ($request->filled('pic')) {
            $rollup->where('verify_by', $request->pic);
        }
        if ($request->filled('line')) {
            $lineFilter = $request->integer('line');
            $rollup->whereIn('customer', function ($sub) use ($lineFilter) {
                $sub->select('name')->from('customers')->where('line', $lineFilter);
            });
        }

        $heavy = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($rollup) {
            $this->ensureRollupReady();

            $statsRow = (clone $rollup)
                ->selectRaw('COUNT(DISTINCT bsthp_no) as total_bsthp')
                ->selectRaw('COUNT(DISTINCT code_item) as total_code_item')
                ->selectRaw('COALESCE(SUM(label_count), 0) as total_barcode')
                ->selectRaw('COUNT(DISTINCT customer) as total_customer')
                ->selectRaw('COALESCE(SUM(row_count), 0) as total_rows')
                ->selectRaw('COALESCE(SUM(qty_sum), 0) as total_qty')
                ->first();

            $totalBsthp = (int) $statsRow->total_bsthp;
            $totalCodeItem = (int) $statsRow->total_code_item;
            $totalBarcode = (int) $statsRow->total_barcode;
            $totalCustomer = (int) $statsRow->total_customer;
            $totalRows = (int) $statsRow->total_rows;
            $totalQty = (float) $statsRow->total_qty;

            // Jumlah item yang diverifikasi per PIC (+ jumlah BSTHP unik per PIC untuk grafik).
            $verifiedByPic = (clone $rollup)
                ->whereNotNull('verify_by')
                ->select('verify_by')
                ->selectRaw('CAST(SUM(row_count) AS UNSIGNED) as jumlah')
                ->selectRaw('COUNT(DISTINCT bsthp_no) as bsthp_count')
                ->groupBy('verify_by')
                ->orderByDesc('jumlah')
                ->get();

            $totalVerified = $verifiedByPic->sum('jumlah');
            $picBsthpChartData = $this->buildPicBsthpChartData($verifiedByPic);

            $hourlyArrivalChartData = $this->buildHourlyArrivalChartData($rollup);

            // Top customer (jumlah BSTHP, total qty, total barcode).
            $topCustomers = (clone $rollup)
                ->whereNotNull('receiving_goods_rollup.customer')
                ->leftJoin('customers', 'customers.name', '=', 'receiving_goods_rollup.customer')
                ->select('receiving_goods_rollup.customer', 'customers.line as line')
                ->selectRaw('COUNT(DISTINCT receiving_goods_rollup.bsthp_no) as jumlah_bsthp')
                ->selectRaw('COALESCE(SUM(receiving_goods_rollup.qty_sum), 0) as total_qty')
                ->selectRaw('COALESCE(SUM(receiving_goods_rollup.label_count), 0) as total_barcode')
                ->groupBy('receiving_goods_rollup.customer', 'customers.line')
                ->orderByDesc('jumlah_bsthp')
                ->orderBy('receiving_goods_rollup.customer')
                ->get();

            // Pie chart customer per Line, diturunkan dari $topCustomers (tanpa query tambahan).
            $customerByLineChartData = $this->buildCustomerByLineChartData($topCustomers);

            $topItems = (clone $rollup)
                ->whereNotNull('code_item')
                ->select('code_item')
                ->selectRaw('MIN(customer) as customer')
                ->selectRaw('COUNT(DISTINCT bsthp_no) as jumlah_bsthp')
                ->selectRaw('COALESCE(SUM(qty_sum), 0) as total_qty')
                ->selectRaw('COALESCE(SUM(label_count), 0) as total_barcode')
                ->groupBy('code_item')
                ->orderByDesc('jumlah_bsthp')
                ->orderBy('code_item')
                ->get();

            // Grafik tren harian / bulanan / tahunan. COUNT(DISTINCT) tidak bisa
            // dijumlahkan antar periode, jadi tiap periode dihitung langsung (dari
            // tabel ringkasan yang kecil, jadi tetap cepat).
            $chartData = [
                'day' => $this->buildChartSeries($rollup, "DATE_FORMAT(work_date, '%Y-%m-%d')"),
                'month' => $this->buildChartSeries($rollup, "DATE_FORMAT(work_date, '%Y-%m')"),
                'year' => $this->buildChartSeries($rollup, "DATE_FORMAT(work_date, '%Y')"),
            ];

            return compact(
                'totalBsthp', 'totalCodeItem', 'totalBarcode', 'totalCustomer',
                'totalRows', 'totalQty', 'totalVerified', 'verifiedByPic',
                'picBsthpChartData', 'hourlyArrivalChartData', 'customerByLineChartData',
                'topCustomers', 'topItems', 'chartData'
            );
        });
        extract($heavy);

        // Daftar customer & PIC untuk dropdown filter (dari seluruh data, bukan hasil filter).
        // Di-cache singkat karena query ini tidak bergantung pada filter tapi tadinya
        // dijalankan ulang (full scan + join) di setiap request.
        // Dibaca dari tabel ringkasan (kecil), bukan receiving_goods (SELECT DISTINCT + join
        // pada 600rb+ baris butuh beberapa detik). Cache dibuang otomatis setiap ada import.
        $customerOptions = Cache::remember(
            'receiving_goods.customer_options.'.Cache::get('receiving_goods.data_version', '0'),
            now()->addMinutes(30),
            function () {
                $this->ensureRollupReady();

                return DB::table('receiving_goods_rollup as r')
                    ->whereNotNull('r.customer')
                    ->leftJoin('customers', 'customers.name', '=', 'r.customer')
                    ->select('r.customer', 'customers.line as line')
                    ->distinct()
                    ->orderBy('r.customer')
                    ->get();
            }
        );
        $picOptions = Cache::remember(
            'receiving_goods.pic_options.'.Cache::get('receiving_goods.data_version', '0'),
            now()->addMinutes(30),
            function () {
                $this->ensureRollupReady();

                return DB::table('receiving_goods_rollup')->whereNotNull('verify_by')->distinct()->orderBy('verify_by')->pluck('verify_by');
            }
        );
        $lineOptions = Cache::remember(
            'receiving_goods.line_options',
            300,
            fn () => Customer::whereNotNull('line')->distinct()->orderBy('line')->pluck('line')
        );

        // Tabel detail dengan pencarian sederhana + pagination.
        $tableQuery = (clone $baseQuery)
            ->select('receiving_goods.*', 'customers.line as line')
            ->leftJoin('customers', 'customers.name', '=', 'receiving_goods.customer')
            ->orderByDesc('receiving_goods.date_income');
        if ($request->filled('q')) {
            $keyword = $request->q;
            $tableQuery->where(function ($sub) use ($keyword) {
                $sub->where('code_item', 'like', "%{$keyword}%")
                    ->orWhere('part_name', 'like', "%{$keyword}%")
                    ->orWhere('bsthp_no', 'like', "%{$keyword}%")
                    ->orWhere('label_barcode_no', 'like', "%{$keyword}%")
                    ->orWhere('customer', 'like', "%{$keyword}%")
                    ->orWhere('verify_by', 'like', "%{$keyword}%");
            });
        }
        // Tanpa pencarian teks, jumlah baris tabel = $totalRows (agregasi dengan filter yang sama),
        // jadi COUNT(*) tambahan pada tabel besar bisa dilewati.
        $rows = $request->filled('q')
            ? $tableQuery->paginate(25)->withQueryString()
            : $tableQuery->paginate(25, ['*'], 'page', null, $totalRows)->withQueryString();

        return view('dashboard', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'boundsMin' => $bounds->min_date,
            'boundsMax' => $bounds->max_date,
            'totalBsthp' => $totalBsthp,
            'totalCodeItem' => $totalCodeItem,
            'totalBarcode' => $totalBarcode,
            'totalCustomer' => $totalCustomer,
            'totalRows' => $totalRows,
            'totalQty' => $totalQty,
            'totalVerified' => $totalVerified,
            'verifiedByPic' => $verifiedByPic,
            'picBsthpChartData' => $picBsthpChartData,
            'hourlyArrivalChartData' => $hourlyArrivalChartData,
            'customerByLineChartData' => $customerByLineChartData,
            'topCustomers' => $topCustomers,
            'topItems' => $topItems,
            'customerOptions' => $customerOptions,
            'picOptions' => $picOptions,
            'lineOptions' => $lineOptions,
            'rows' => $rows,
            'filters' => $request->only(['date_from', 'date_to', 'customer', 'pic', 'line', 'q']),
            'hasData' => ReceivingGood::query()->exists(),
            'chartData' => $chartData,
        ]);
    }

    /**
     * Hitung jumlah BSTHP unik yang dikirimkan per PIC verifikator.
     */
    protected function buildPicBsthpChartData($verifiedByPic): array
    {
        $result = $verifiedByPic
            ->filter(fn ($row) => (int) $row->bsthp_count > 0)
            ->sortBy([['bsthp_count', 'desc'], ['verify_by', 'asc']])
            ->values();

        return [
            'labels' => $result->pluck('verify_by')->all(),
            'values' => $result->pluck('bsthp_count')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * Hitung jumlah BSTHP (unik) berdasarkan jam kedatangan (00:00 s.d. 23:00),
     * dari tabel ringkasan, mengikuti filter yang sedang aktif.
     */
    protected function buildHourlyArrivalChartData($rollup): array
    {
        $result = (clone $rollup)
            ->select('work_hour as hour')
            ->selectRaw('COUNT(DISTINCT bsthp_no) as bsthp_count')
            ->selectRaw('COALESCE(SUM(row_count), 0) as rows_count')
            ->groupBy('work_hour')
            ->orderBy('work_hour')
            ->get()
            ->keyBy('hour');

        $labels = [];
        $values = [];
        $rowsCount = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $row = $result->get($hour);

            $labels[] = sprintf('%02d:00', $hour);
            $values[] = $row ? (int) $row->bsthp_count : 0;
            $rowsCount[] = $row ? (int) $row->rows_count : 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'rows' => $rowsCount,
        ];
    }

    /**
     * Hitung jumlah customer (unik) per Line, untuk pie chart "Customer Berdasarkan Line".
     * Customer yang belum punya Line di tabel master dikelompokkan sebagai "Tanpa Line".
     */
    protected function buildCustomerByLineChartData($topCustomers): array
    {
        $groups = [];
        foreach ($topCustomers as $row) {
            $lineValue = (int) ($row->line ?? 0);
            if (! isset($groups[$lineValue])) {
                $groups[$lineValue] = ['customers' => [], 'total_barcode' => 0];
            }
            if ($row->customer !== null && $row->customer !== '') {
                $groups[$lineValue]['customers'][$row->customer] = true;
            }
            $groups[$lineValue]['total_barcode'] += (int) $row->total_barcode;
        }

        // Urutkan sesuai perilaku semula: total_barcode desc, lalu line_group asc.
        uksort($groups, function ($a, $b) use ($groups) {
            $cmp = $groups[$b]['total_barcode'] <=> $groups[$a]['total_barcode'];
            return $cmp !== 0 ? $cmp : ($a <=> $b);
        });

        $labels = [];
        $values = [];
        $totalBarcode = [];
        $details = [];

        foreach ($groups as $lineValue => $data) {
            $label = $lineValue === 0 ? 'Tanpa Line' : 'Line '.$lineValue;
            $customers = array_values(array_keys($data['customers']));
            sort($customers);

            $labels[] = $label;
            $values[] = count($data['customers']);
            $totalBarcode[] = $data['total_barcode'];
            $details[] = [
                'label' => $label,
                'customers' => $customers,
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total_barcode' => $totalBarcode,
            'details' => $details,
        ];
    }

    /**
     * Hitung tren jumlah BSTHP (unik), Customer (unik), dan item yang diverifikasi PIC
     * dikelompokkan per periode ($periodExpr = ekspresi SQL atas kolom work_date),
     * dari tabel ringkasan.
     */
    protected function buildChartSeries($rollup, string $periodExpr): array
    {
        $result = (clone $rollup)
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw('COUNT(DISTINCT bsthp_no) as bsthp_count')
            ->selectRaw('COUNT(DISTINCT customer) as customer_count')
            ->selectRaw('COUNT(DISTINCT verify_by) as pic_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN verify_by IS NOT NULL THEN row_count ELSE 0 END), 0) as verified_count')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $result->pluck('period')->all(),
            'bsthp' => $result->pluck('bsthp_count')->map(fn ($v) => (int) $v)->all(),
            'customer' => $result->pluck('customer_count')->map(fn ($v) => (int) $v)->all(),
            'pic' => $result->pluck('pic_count')->map(fn ($v) => (int) $v)->all(),
            'verified' => $result->pluck('verified_count')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * Pastikan tabel ringkasan sudah ada dan terisi; kalau belum, beri pesan yang jelas
     * (bukan dashboard kosong yang menyesatkan).
     */
    protected function ensureRollupReady(): void
    {
        $missing = ! Schema::hasTable('receiving_goods_rollup');
        $empty = ! $missing
            && ! DB::table('receiving_goods_rollup')->exists()
            && ReceivingGood::query()->exists();

        if ($missing || $empty) {
            throw new \RuntimeException(
                'Tabel ringkasan dashboard belum dibuat/terisi. Jalankan di terminal: '.
                'php artisan migrate  lalu  php artisan rollup:rebuild'
            );
        }
    }
}
