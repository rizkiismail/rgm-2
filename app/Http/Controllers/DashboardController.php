<?php

namespace App\Http\Controllers;

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

        // Klon query dasar untuk masing-masing statistik agar tidak saling mempengaruhi.
        $totalBsthp = (clone $baseQuery)->distinct('bsthp_no')->count('bsthp_no');
        $totalCodeItem = (clone $baseQuery)->distinct('code_item')->count('code_item');
        $totalBarcode = (clone $baseQuery)->whereNotNull('label_barcode_no')->distinct('label_barcode_no')->count('label_barcode_no');
        $totalCustomer = (clone $baseQuery)->distinct('customer')->count('customer');
        $totalRows = (clone $baseQuery)->count();
        $totalQty = (clone $baseQuery)->sum('qty');

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

        // Top 10 customer berdasarkan jumlah BSTHP, total qty, dan total barcode.
        $topCustomers = (clone $baseQuery)
            ->whereNotNull('customer')
            ->select(
                'customer',
                DB::raw('COUNT(DISTINCT bsthp_no) as jumlah_bsthp'),
                DB::raw('COALESCE(SUM(qty), 0) as total_qty'),
                DB::raw('COUNT(DISTINCT CASE WHEN label_barcode_no IS NOT NULL THEN label_barcode_no END) as total_barcode')
            )
            ->groupBy('customer')
            ->orderByDesc('jumlah_bsthp')
            ->orderBy('customer')
            ->get();

        // Top 10 item berdasarkan jumlah BSTHP, total qty, dan total barcode.
        $topItems = (clone $baseQuery)
            ->whereNotNull('code_item')
            ->select(
                'code_item',
                DB::raw('COUNT(DISTINCT bsthp_no) as jumlah_bsthp'),
                DB::raw('COALESCE(SUM(qty), 0) as total_qty'),
                DB::raw('COUNT(DISTINCT CASE WHEN label_barcode_no IS NOT NULL THEN label_barcode_no END) as total_barcode')
            )
            ->groupBy('code_item')
            ->orderByDesc('jumlah_bsthp')
            ->orderBy('code_item')
            ->get();

        // Daftar customer & PIC untuk dropdown filter (dari seluruh data, bukan hasil filter).
        $customerOptions = ReceivingGood::whereNotNull('customer')->distinct()->orderBy('customer')->pluck('customer');
        $picOptions = ReceivingGood::whereNotNull('verify_by')->distinct()->orderBy('verify_by')->pluck('verify_by');

        // Tabel detail dengan pencarian sederhana + pagination.
        $tableQuery = (clone $baseQuery)->orderByDesc('date_income');
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
            'topCustomers' => $topCustomers,
            'topItems' => $topItems,
            'customerOptions' => $customerOptions,
            'picOptions' => $picOptions,
            'rows' => $rows,
            'filters' => $request->only(['date_from', 'date_to', 'customer', 'pic', 'q']),
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
