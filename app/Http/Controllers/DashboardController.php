<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ReceivingGood;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        // Satu query tunggal untuk seluruh statistik scalar (dulu 6 query terpisah
        // yang masing-masing full scan tabel yang sama -> salah satu penyebab timeout).
        $statsRow = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT bsthp_no) as total_bsthp')
            ->selectRaw('COUNT(DISTINCT code_item) as total_code_item')
            ->selectRaw('COUNT(DISTINCT CASE WHEN label_barcode_no IS NOT NULL THEN label_barcode_no END) as total_barcode')
            ->selectRaw('COUNT(DISTINCT customer) as total_customer')
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty')
            ->first();

        $totalBsthp = (int) $statsRow->total_bsthp;
        $totalCodeItem = (int) $statsRow->total_code_item;
        $totalBarcode = (int) $statsRow->total_barcode;
        $totalCustomer = (int) $statsRow->total_customer;
        $totalRows = (int) $statsRow->total_rows;
        $totalQty = (float) $statsRow->total_qty;

        // Breakdown jumlah item yang diverifikasi per PIC, mis. "DANDI: 5"
        $verifiedByPic = (clone $baseQuery)
            ->whereNotNull('verify_by')
            ->select('verify_by', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('verify_by')
            ->orderByDesc('jumlah')
            ->get();

        $totalVerified = $verifiedByPic->sum('jumlah');

        // Breakdown jumlah BSTHP unik per PIC verifikator untuk grafik khusus.
        $picBsthpChartData = $this->buildPicBsthpChartData($baseQuery);

        // Breakdown jumlah customer (unik) per Line untuk pie chart.
        $customerByLineChartData = $this->buildCustomerByLineChartData($baseQuery);

        // Top 10 customer berdasarkan jumlah BSTHP, total qty, dan total barcode.
        $topCustomers = (clone $baseQuery)
            ->whereNotNull('customer')
            ->leftJoin('customers', 'customers.name', '=', 'receiving_goods.customer')
            ->select(
                'receiving_goods.customer',
                'customers.line as line',
                DB::raw('COUNT(DISTINCT receiving_goods.bsthp_no) as jumlah_bsthp'),
                DB::raw('COALESCE(SUM(receiving_goods.qty), 0) as total_qty'),
                DB::raw('COUNT(DISTINCT CASE WHEN receiving_goods.label_barcode_no IS NOT NULL THEN receiving_goods.label_barcode_no END) as total_barcode')
            )
            ->groupBy('receiving_goods.customer', 'customers.line')
            ->orderByDesc('jumlah_bsthp')
            ->orderBy('receiving_goods.customer')
            ->get();

        // Top 10 item berdasarkan jumlah BSTHP, total qty, dan total barcode.
        $topItems = (clone $baseQuery)
            ->whereNotNull('code_item')
            ->select(
                'code_item',
                DB::raw('MIN(customer) as customer'),
                DB::raw('COUNT(DISTINCT bsthp_no) as jumlah_bsthp'),
                DB::raw('COALESCE(SUM(qty), 0) as total_qty'),
                DB::raw('COUNT(DISTINCT CASE WHEN label_barcode_no IS NOT NULL THEN label_barcode_no END) as total_barcode')
            )
            ->groupBy('code_item')
            ->orderByDesc('jumlah_bsthp')
            ->orderBy('code_item')
            ->get();

        // Daftar customer & PIC untuk dropdown filter (dari seluruh data, bukan hasil filter).
        // Di-cache singkat karena query ini tidak bergantung pada filter tapi tadinya
        // dijalankan ulang (full scan + join) di setiap request.
        $customerOptions = \Illuminate\Support\Facades\Cache::remember(
            'receiving_goods.customer_options',
            300,
            fn () => ReceivingGood::query()
                ->whereNotNull('customer')
                ->leftJoin('customers', 'customers.name', '=', 'receiving_goods.customer')
                ->select('receiving_goods.customer', 'customers.line as line')
                ->distinct()
                ->orderBy('receiving_goods.customer')
                ->get()
        );
        $picOptions = \Illuminate\Support\Facades\Cache::remember(
            'receiving_goods.pic_options',
            300,
            fn () => ReceivingGood::whereNotNull('verify_by')->distinct()->orderBy('verify_by')->pluck('verify_by')
        );
        $lineOptions = \Illuminate\Support\Facades\Cache::remember(
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
        $rows = $tableQuery->paginate(25)->withQueryString();

        // Data grafik tren (Harian / Bulanan / Tahunan) untuk BSTHP, Customer, dan PIC Verifikator.
        // Dihitung sekali untuk ketiga periode sekaligus supaya toggle di frontend tidak perlu reload halaman.
        $chartData = [
            'day' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(date_income, '%Y-%m-%d')"),
            'month' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(date_income, '%Y-%m')"),
            'year' => $this->buildChartSeries($baseQuery, "DATE_FORMAT(date_income, '%Y')"),
        ];

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
    protected function buildPicBsthpChartData($baseQuery): array
    {
        $result = (clone $baseQuery)
            ->whereNotNull('verify_by')
            ->whereNotNull('bsthp_no')
            ->select('verify_by', DB::raw('COUNT(DISTINCT bsthp_no) as bsthp_count'))
            ->groupBy('verify_by')
            ->orderByDesc('bsthp_count')
            ->orderBy('verify_by')
            ->get();

        return [
            'labels' => $result->pluck('verify_by')->all(),
            'values' => $result->pluck('bsthp_count')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * Hitung jumlah customer (unik) per Line, untuk pie chart "Customer Berdasarkan Line".
     * Customer yang belum punya Line di tabel master dikelompokkan sebagai "Tanpa Line".
     */
    protected function buildCustomerByLineChartData($baseQuery): array
    {
        // Satu query yang mengelompokkan (line_group, customer) sekaligus, lalu
        // di-agregasi di PHP. Sebelumnya kode ini menjalankan 1 query ringkasan
        // + 1 query tambahan PER line group (N+1) -- pada dataset besar dengan
        // banyak Line ini adalah salah satu penyebab utama timeout 60 detik.
        $rows = (clone $baseQuery)
            ->whereNotNull('customer')
            ->leftJoin('customers', 'customers.name', '=', 'receiving_goods.customer')
            ->selectRaw('COALESCE(customers.line, 0) as line_group')
            ->selectRaw('receiving_goods.customer as customer')
            ->selectRaw('COUNT(DISTINCT CASE WHEN receiving_goods.label_barcode_no IS NOT NULL THEN receiving_goods.label_barcode_no END) as total_barcode')
            ->groupBy('line_group', 'receiving_goods.customer')
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $lineValue = (int) $row->line_group;
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
     * dikelompokkan berdasarkan ekspresi periode (harian/bulanan/tahunan).
     */
    protected function buildChartSeries($baseQuery, string $periodExpr): array
    {
        $result = (clone $baseQuery)
            ->whereNotNull('date_income')
            ->selectRaw("{$periodExpr} as period")
            ->selectRaw('COUNT(DISTINCT bsthp_no) as bsthp_count')
            ->selectRaw('COUNT(DISTINCT customer) as customer_count')
            ->selectRaw('COUNT(DISTINCT verify_by) as pic_count')
            ->selectRaw('SUM(CASE WHEN verify_by IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
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
}
