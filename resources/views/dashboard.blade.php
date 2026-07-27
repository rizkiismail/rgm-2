@extends('layout')

@section('title', 'Dashboard - Monitoring Receiving Goods')

@section('content')

    @if (!$hasData)
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-1"></i>
            Belum ada data. Silakan <a href="{{ route('import.form') }}" class="alert-link">upload file export</a> terlebih dahulu.
        </div>
    @endif

    {{-- ===================== FILTER ===================== --}}
    <div class="card filter-card sticky-filter mb-4">
        <div class="card-body" style="font-size: 0.80rem;">
            <button id="filterToggle" type="button"
                    class="btn btn-outline-secondary w-100 justify-content-between align-items-center mb-0"
                    data-bs-toggle="collapse" data-bs-target="#filterCollapse"
                    aria-expanded="false" aria-controls="filterCollapse">
                <span><i class="bi bi-funnel-fill me-2"></i>Filter Data</span>
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="collapse filter-collapse-body mt-3 mt-md-0" id="filterCollapse">
            <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Tanggal Dari</label>
                    <input type="date" name="date_from" class="form-control"
                           value="{{ $filters['date_from'] ?? ($dateFrom?->format('Y-m-d')) }}" style="font-size: 0.80rem;">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Tanggal Sampai</label>
                    <input type="date" name="date_to" class="form-control"
                           value="{{ $filters['date_to'] ?? ($dateTo?->format('Y-m-d')) }}" style="font-size: 0.80rem;">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Customer</label>
                    <select name="customer" class="form-select" style="font-size: 0.80rem;">
                        <option value="" >Semua Customer</option>
                        @foreach ($customerOptions as $c)
                            <option value="{{ $c->customer }}" @selected(($filters['customer'] ?? '') === $c->customer)>
                                {{ $c->customer }}{{ $c->line ? ' (Line '.$c->line.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">PIC Verifikator</label>
                    <select name="pic" class="form-select" style="font-size: 0.80rem;">
                        <option value="" >Semua PIC</option>
                        @foreach ($picOptions as $p)
                            <option value="{{ $p }}" @selected(($filters['pic'] ?? '') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label small text-muted mb-1">Line</label>
                    <select name="line" class="form-select" style="font-size: 0.80rem;">
                        <option value="" >Semua Line</option>
                        @foreach ($lineOptions as $l)
                            <option value="{{ $l }}" @selected((string) ($filters['line'] ?? '') === (string) $l)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Cari (Code Item / Part / BSTHP / Barcode)</label>
                    <input type="text" name="q" class="form-control" style="font-size: 0.80rem;" placeholder="Ketik kata kunci..."
                           value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="col-12 col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i></button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
            @if ($boundsMin && $boundsMax)
                <div class="small text-muted mt-2">
                    <i class="bi bi-calendar-range"></i>
                    Data tersedia dari <strong>{{ \Illuminate\Support\Carbon::parse($boundsMin)->translatedFormat('d M Y') }}</strong>
                    sampai <strong>{{ \Illuminate\Support\Carbon::parse($boundsMax)->translatedFormat('d M Y') }}</strong>.
                </div>
            @endif
            </div>
        </div>
    </div>

    <div class="mb-4 section-nav-wrapper">
        <button id="sectionNavToggle" type="button"
                class="btn btn-outline-secondary w-100 justify-content-between align-items-center rounded-3 shadow-sm mb-2"
                data-bs-toggle="collapse" data-bs-target="#sectionNavCollapse"
                aria-expanded="false" aria-controls="sectionNavCollapse">
            <span><i class="bi bi-list-ul me-2"></i>Navigasi Bagian</span>
            <i class="bi bi-list fs-5"></i>
        </button>
        <nav id="sectionNavCollapse" class="nav nav-pills collapse flex-column flex-md-row flex-md-nowrap overflow-auto gap-2 bg-white p-2 rounded-3 shadow-sm section-nav">
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link active" href="#section-summary" data-section="section-summary" style="font-size: 0.80rem;">Ringkasan</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-trends" data-section="section-trends" style="font-size: 0.80rem;">Tren BSTHP Customer &amp; PIC Verifikator</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-pic-bsthp" data-section="section-pic-bsthp" style="font-size: 0.80rem;">Jumlah BSTHP Berdasarkan PIC Verifikator</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-customer-line" data-section="section-customer-line" style="font-size: 0.80rem;">Customer Berdasarkan Line</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-top10" data-section="section-top10" style="font-size: 0.80rem;">Top 10 Customer &amp; Item</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" data-section="section-detail-data" href="#section-detail-data" style="font-size: 0.80rem;">Detail data</a>
        </nav>
    </div>

    {{-- ===================== SUMMARY CARDS ===================== --}}
    <div class="row g-3 mb-4" id="section-summary">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Jumlah Data BSTHP</div>
                            <div class="stat-value text-primary">{{ number_format($totalBsthp) }}</div>
                        </div>
                        <i class="bi bi-file-earmark-text-fill stat-icon text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Item Diverifikasi PIC</div>
                            <div class="stat-value text-success">{{ number_format($totalVerified) }}</div>
                        </div>
                        <i class="bi bi-patch-check-fill stat-icon text-success"></i>
                    </div>
                    <button class="btn btn-sm btn-link p-0 mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#picBreakdown">
                        Lihat rincian per PIC <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Code Item (Unik)</div>
                            <div class="stat-value text-warning">{{ number_format($totalCodeItem) }}</div>
                        </div>
                        <i class="bi bi-upc-scan stat-icon text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Jumlah Barcode</div>
                            <div class="stat-value text-info">{{ number_format($totalBarcode) }}</div>
                        </div>
                        <i class="bi bi-qr-code stat-icon text-info"></i>
                    </div>
                    <div class="small text-muted mt-1">Label Barcode No (unik)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Customer (Unik)</div>
                            <div class="stat-value text-danger">{{ number_format($totalCustomer) }}</div>
                        </div>
                        <i class="bi bi-building-fill stat-icon text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Qty</div>
                            <div class="stat-value text-dark">{{ number_format($totalQty) }}</div>
                        </div>
                        <i class="bi bi-boxes stat-icon text-dark"></i>
                    </div>
                    <div class="small text-muted mt-1">{{ number_format($totalRows) }} baris data</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== PIC BREAKDOWN (collapsible) ===================== --}}
    <div class="collapse mb-4" id="picBreakdown">
        <div class="card table-card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-check-fill text-success"></i> Rincian Item Diverifikasi per PIC
            </div>
            <div class="card-body">
                @if ($verifiedByPic->isEmpty())
                    <p class="text-muted mb-0">Tidak ada data verifikasi pada rentang/filter ini.</p>
                @else
                    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-2">
                        @foreach ($verifiedByPic as $pic)
                            <div class="col">
                                <div class="border rounded-3 p-2 d-flex justify-content-between align-items-center">
                                    <span class="text-truncate" title="{{ $pic->verify_by }}">{{ $pic->verify_by }}</span>
                                    <span class="badge bg-success badge-pic">{{ number_format($pic->jumlah) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== GRAFIK TREN ===================== --}}
    <div class="card table-card mb-6" id="section-trends">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><i class="bi bi-graph-up-arrow text-primary"></i> Grafik Tren BSTHP, Customer &amp; PIC Verifikator</span>
            <div class="btn-group btn-group-sm" role="group" id="chartPeriodToggle">
                <button type="button" class="btn btn-outline-primary active" data-period="day">Harian</button>
                <button type="button" class="btn btn-outline-primary" data-period="month">Bulanan</button>
                <button type="button" class="btn btn-outline-primary" data-period="year">Tahunan</button>
            </div>
        </div>
        <div class="card-body">
            @if (empty($chartData['day']['labels']) && empty($chartData['month']['labels']))
                <p class="text-muted mb-0">Belum ada data bertanggal untuk ditampilkan pada grafik.</p>
            @else
                <div class="row g-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <h6 class="text-muted small text-uppercase mb-2">Jumlah BSTHP</h6>
                        <div class="chart-box" style="position: relative; height: 320px; padding-top: 8px;">
                            <canvas id="chartBsthp"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <h6 class="text-muted small text-uppercase mb-2">Jumlah Customer</h6>
                        <div class="chart-box" style="position: relative; height: 320px; padding-top: 8px;">
                            <canvas id="chartCustomer"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <h6 class="text-muted small text-uppercase mb-2">Total Item Terverifikasi</h6>
                        <div class="chart-box" style="position: relative; height: 320px; padding-top: 8px;">
                            <canvas id="chartPic"></canvas>
                        </div>
                    </div>
                                    </div>
            @endif
        </div>
    </div>

    {{-- ===================== GRAFIK BSTHP PER PIC ===================== --}}
    <div class="card table-card mb-4" id="section-pic-bsthp">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-bar-chart-line-fill text-primary"></i> Grafik Jumlah BSTHP Berdasarkan PIC Verifikator
        </div>
        <div class="card-body">
            @if (empty($picBsthpChartData['labels']))
                <p class="text-muted mb-0">Belum ada data verifikasi PIC untuk ditampilkan.</p>
            @else
                <div class="row g-4">
                    <div class="col-12">
                        <div class="chart-box" style="position: relative; height: 380px; padding-top: 8px;">
                            <canvas id="chartPicBsthp"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== GRAFIK CUSTOMER PER LINE ===================== --}}
    <div class="card table-card mb-4" id="section-customer-line">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-pie-chart-fill text-danger"></i> Grafik Customer Berdasarkan Line
        </div>
        <div class="card-body">
            @if (empty($customerByLineChartData['labels']))
                <p class="text-muted mb-0">Belum ada data customer dengan Line untuk ditampilkan.</p>
            @else
                <div class="row g-4 align-items-center">
                    <div class="col-12 col-md-6">
                        <div class="chart-box" style="position: relative; height: 360px;">
                            <canvas id="chartCustomerByLine"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="table-responsive" style="max-height: 360px;">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead>
                                <tr>
                                    <th>Line</th>
                                    <th class="text-end">Jumlah Customer</th>
                                    <th class="text-end">Total Barcode</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($customerByLineChartData['labels'] as $i => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end">
                                            <details class="d-inline-block">
                                                <summary class="btn btn-link p-0 fw-semibold text-primary">
                                                    {{ number_format($customerByLineChartData['values'][$i]) }}
                                                </summary>
                                                <div class="mt-2 border rounded p-2 bg-light" style="min-width: 240px;">
                                                    <div class="small text-muted mb-2">Customer di {{ $label }}</div>
                                                    @php $customers = $customerByLineChartData['details'][$i]['customers'] ?? []; @endphp
                                                    @if (empty($customers))
                                                        <div class="small text-muted">Tidak ada customer.</div>
                                                    @else
                                                        <ol class="mb-0 ps-3 small">
                                                            @foreach ($customers as $customer)
                                                                <li>{{ $customer }}</li>
                                                            @endforeach
                                                        </ol>
                                                    @endif
                                                </div>
                                            </details>
                                        </td>
                                        <td class="text-end">{{ number_format($customerByLineChartData['total_barcode'][$i] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== TOP 10 CUSTOMER & ITEM ===================== --}}
    <div class="row g-4 mb-4" >
        <div class="col-12 col-xl-6">
            <div class="card table-card h-100" id="section-top10">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-people-fill text-primary"></i> Top 10 Customer
                </div>
                <div class="table-responsive top10-table-wrap" data-visible-rows="10" style="font-size: 0.60rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle">
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Customer</th>
                            <th class="text-center">Line</th>
                            <th class="text-end">Jumlah BSTHP</th>
                            <th class="text-end">Total Qty</th>
                            <th class="text-end">Total Barcode</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topCustomers as $customer)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $customer->customer }}</td>
                                <td class="text-center">
                                    @if ($customer->line)
                                        <span class="badge bg-secondary">{{ $customer->line }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($customer->jumlah_bsthp) }}</td>
                                <td class="text-end">{{ number_format((float) $customer->total_qty, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($customer->total_barcode) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data customer untuk filter ini.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card table-card h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-box-seam text-success"></i> Top 10 Item
                </div>
                <div class="table-responsive top10-table-wrap" data-visible-rows="10" style="font-size: 0.60rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle" >
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Customer</th>
                            <th>Item</th>
                            <th class="text-end">Jumlah BSTHP</th>
                            <th class="text-end">Total Qty</th>
                            <th class="text-end">Total Barcode</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topItems as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $item->customer ?? '-' }}</td>
                                <td>{{ $item->code_item }}</td>
                                <td class="text-end">{{ number_format($item->jumlah_bsthp) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_qty, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item->total_barcode) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data item untuk filter ini.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== DATA TABLE ===================== --}}
    <div class="card table-card" id="section-detail-data">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table"></i> Detail Data ({{ number_format($rows->total()) }} baris sesuai filter)</span>
        </div>
        <div class="table-responsive" style="max-height: 65vh;">
            <table class="table table-sm table-hover table-striped mb-0 align-middle" >
                <thead class="sticky-th">
                <tr>
                    <th>No.</th>
                    <th>Tanggal Terima</th>
                    <th>No. BSTHP</th>
                    <th>PIC Verifikasi</th>
                    <th>Code Item</th>
                    <th>Part Name</th>
                    <th>Model</th>
                    <th class="text-end">Qty</th>
                    <th>Unit</th>
                    <th>Label Barcode</th>
                    <th>Customer</th>
                    <th class="text-center">Line</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>
                        <td class="text-nowrap">{{ $row->date_income?->format('d-m-Y H:i') }}</td>
                        <td class="text-nowrap">{{ $row->bsthp_no }}</td>
                        <td>{{ $row->verify_by }}</td>
                        <td>{{ $row->code_item }}</td>
                        <td>{{ $row->part_name }}</td>
                        <td>{{ $row->model }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty) }}</td>
                        <td>{{ $row->unit }}</td>
                        <td class="text-nowrap">{{ $row->label_barcode_no }}</td>
                        <td>{{ $row->customer }}</td>
                        <td class="text-center">
                            @if ($row->line)
                                <span class="badge bg-secondary">{{ $row->line }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Tidak ada data untuk filter ini.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $rows->links() }}
        </div>
    </div>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script>
        Chart.register(ChartDataLabels);

        const chartData = @json($chartData);
        const picBsthpChartData = @json($picBsthpChartData);
        const customerByLineChartData = @json($customerByLineChartData);

        const periodLabelFormatters = {
            day: (v) => v,
            month: (v) => v,
            year: (v) => v,
        };

        function formatLabels(period, labels) {
            return labels.map(periodLabelFormatters[period]);
        }

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { clip: false },
            },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        };

        let currentPeriod = 'day';
        let chartBsthp, chartCustomer, chartPic, chartPicBsthp, chartPicBsthpPie, chartCustomerByLine;

        /**
         * Palet warna konsisten untuk tiap PIC (dipakai bareng oleh bar & pie chart).
         */
        function picColorPalette(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                const hue = Math.round((360 / Math.max(count, 1)) * i);
                colors.push(`hsl(${hue}, 70%, 55%)`);
            }
            return colors;
        }

        function buildPicBsthpChart() {
            const ctx = document.getElementById('chartPicBsthp');
            if (!ctx || !picBsthpChartData.labels?.length) return;

            if (chartPicBsthp) chartPicBsthp.destroy();

            chartPicBsthp = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: picBsthpChartData.labels,
                    datasets: [{
                        label: 'Jumlah BSTHP',
                        data: picBsthpChartData.values,
                        backgroundColor: '#198754',
                        borderRadius: 4,
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => Number(value).toLocaleString('id-ID'),
                        },
                    }],
                },
                options: {
                    ...commonOptions,
                    plugins: { legend: { display: false }, datalabels: { clip: false } },
                    layout: { padding: { top: 16, bottom: 8 } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                        },
                    },
                },
            });
        }

        function buildPicBsthpPieChart() {
            const ctx = document.getElementById('chartPicBsthpPie');
            if (!ctx || !picBsthpChartData.labels?.length) return;

            if (chartPicBsthpPie) chartPicBsthpPie.destroy();

            const colors = picColorPalette(picBsthpChartData.labels.length);
            const total = picBsthpChartData.values.reduce((a, b) => a + b, 0);

            chartPicBsthpPie = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: picBsthpChartData.labels,
                    datasets: [{
                        data: picBsthpChartData.values,
                        backgroundColor: colors,
                        borderColor: '#fff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } },
                        },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 10 },
                            formatter: (value) => {
                                if (!total) return '';
                                const pct = (value / total) * 100;
                                return pct >= 4 ? pct.toFixed(0) + '%' : '';
                            },
                        },
                    },
                },
            });
        }

        function buildCustomerByLineChart() {
            const ctx = document.getElementById('chartCustomerByLine');
            if (!ctx || !customerByLineChartData.labels?.length) return;

            if (chartCustomerByLine) chartCustomerByLine.destroy();

            const colors = picColorPalette(customerByLineChartData.labels.length);
            const total = customerByLineChartData.values.reduce((a, b) => a + b, 0);

            chartCustomerByLine = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: customerByLineChartData.labels,
                    datasets: [{
                        data: customerByLineChartData.values,
                        backgroundColor: colors,
                        borderColor: '#fff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: { boxWidth: 12, font: { size: 11 } },
                        },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 10 },
                            formatter: (value) => {
                                if (!total) return '';
                                const pct = (value / total) * 100;
                                return pct >= 3 ? pct.toFixed(0) + '%' : '';
                            },
                        },
                    },
                },
            });
        }

        function buildCharts(period) {
            const d = chartData[period];
            const labels = formatLabels(period, d.labels);

            if (chartBsthp) chartBsthp.destroy();
            if (chartCustomer) chartCustomer.destroy();
            if (chartPic) chartPic.destroy();

            const ctxBsthp = document.getElementById('chartBsthp');
            const ctxCustomer = document.getElementById('chartCustomer');
            const ctxPic = document.getElementById('chartPic');
            if (!ctxBsthp || !ctxCustomer || !ctxPic) return;

            chartBsthp = new Chart(ctxBsthp, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Jumlah BSTHP',
                        data: d.bsthp,
                        backgroundColor: '#0d6efd',
                        borderRadius: 4,
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => Number(value).toLocaleString('id-ID'),
                        },
                    }],
                },
                options: commonOptions,
            });

            chartCustomer = new Chart(ctxCustomer, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Jumlah Customer',
                        data: d.customer,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.16)',
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        datalabels: {
                            anchor: 'top',
                            align: 'top',
                            formatter: (value) => Number(value).toLocaleString('id-ID'),
                        },
                    }],
                },
                options: {
                    ...commonOptions,
                    layout: { padding: { top: 16, bottom: 8 } },
                },
            });

            chartPic = new Chart(ctxPic, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Item Diverifikasi',
                            data: d.verified,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.16)',
                            borderWidth: 2,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: false,
                            type: 'line',
                            order: 2,
                            datalabels: {
                                anchor: 'top',
                                align: 'top',
                                formatter: (value) => Number(value).toLocaleString('id-ID'),
                            },
                        },
                    ],
                },
                options: {
                    ...commonOptions,
                    plugins: { legend: { display: false }, datalabels: { clip: false } },
                    layout: { padding: { top: 16, bottom: 8 } },
                },
            });
        }

        document.querySelectorAll('.section-nav-link').forEach((link) => {
            link.addEventListener('click', () => {
                document.querySelectorAll('.section-nav-link').forEach((item) => {
                    item.classList.remove('active', 'btn-primary');
                    item.classList.add('btn-outline-primary');
                });
                link.classList.remove('btn-outline-primary');
                link.classList.add('active', 'btn-primary');
            });
        });

        const sectionLinks = Array.from(document.querySelectorAll('.section-nav-link'));
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                sectionLinks.forEach((link) => {
                    const isActive = link.dataset.section === entry.target.id;
                    link.classList.toggle('active', isActive);
                    link.classList.toggle('btn-primary', isActive);
                    link.classList.toggle('btn-outline-primary', !isActive);
                });
            });
        }, { rootMargin: '-30% 0px -55% 0px', threshold: 0.1 });

        document.querySelectorAll('[id^="section-"]').forEach((section) => sectionObserver.observe(section));

        document.querySelectorAll('#chartPeriodToggle button').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#chartPeriodToggle button').forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                currentPeriod = btn.dataset.period;
                buildCharts(currentPeriod);
            });
        });

        if (document.getElementById('chartBsthp')) {
            buildCharts(currentPeriod);
        }

        if (document.getElementById('chartPicBsthp')) {
            buildPicBsthpChart();
        }

        if (document.getElementById('chartPicBsthpPie')) {
            buildPicBsthpPieChart();
        }

        if (document.getElementById('chartCustomerByLine')) {
            buildCustomerByLineChart();
        }
    </script>
@endsection
