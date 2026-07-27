@extends('layout')

@section('title', 'Dashboard - Monitoring Raking Scan Out')

@section('content')

    @if (!$hasData)
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-1"></i>
            Belum ada data. Silakan <a href="{{ route('scanout.import.form') }}" class="alert-link">upload file export Raking Scan Out</a> terlebih dahulu.
        </div>
    @endif

    {{-- ===================== FILTER ===================== --}}
    <div class="card filter-card sticky-filter mb-4">
    <div class="card-body" style="font-size: 0.80rem;">
        <button id="filterToggle" type="button"
        class="btn btn-outline-secondary w-100 d-flex d-lg-none justify-content-between align-items-center mb-0"
        data-bs-toggle="collapse" data-bs-target="#filterCollapse"
        aria-expanded="false" aria-controls="filterCollapse">
    <span><i class="bi bi-funnel-fill me-2"></i>Filter Data</span>
    <i class="bi bi-list fs-5"></i>
</button>

        <div class="collapse filter-collapse-body mt-3 mt-md-0 d-lg-block" id="filterCollapse" >
            <form method="GET" action="{{ route('scanout.dashboard') }}" class="row g-2 align-items-end">

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Tanggal Dari</label>
                    <input type="date" style="font-size: 0.80rem;" name="date_from" class="form-control"
                           value="{{ $filters['date_from'] ?? ($dateFrom?->format('Y-m-d')) }}">
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Tanggal Sampai</label>
                    <input type="date" style="font-size: 0.80rem;" name="date_to" class="form-control"
                           value="{{ $filters['date_to'] ?? ($dateTo?->format('Y-m-d')) }}">
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Customer</label>
                    <select name="customer" class="form-select" style="font-size: 0.80rem;">
                        <option value="">Semua Customer</option>
                        @foreach ($customerOptions as $c)
                            <option value="{{ $c }}" @selected(($filters['customer'] ?? '') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Outgoing Type</label>
                    <select name="outgoing_type" class="form-select" style="font-size: 0.80rem;">
                        <option value="">Semua Tipe</option>
                        @foreach ($outgoingTypeOptions as $t)
                            <option value="{{ $t }}" @selected(($filters['outgoing_type'] ?? '') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">PIC Scan</label>
                    <select name="pic" class="form-select" style="font-size: 0.80rem;">
                        <option value="">Semua PIC</option>
                        @foreach ($picOptions as $p)
                            <option value="{{ $p }}" @selected(($filters['pic'] ?? '') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Line</label>
                    <select name="line" class="form-select" style="font-size: 0.80rem;">
                        <option value="">Semua Line</option>
                        @foreach ($lineOptions as $l)
                            <option value="{{ $l }}" @selected(($filters['line'] ?? '') === $l)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-8 col-lg-8">
                    <label class="form-label small text-muted mb-1">Cari (Barcode / Code Item / Outgoing)</label>
                    <input type="text" style="font-size: 0.80rem;" name="q" class="form-control"
                           placeholder="Ketik kata kunci..."
                           value="{{ $filters['q'] ?? '' }}">
                </div>

                <div class="col-12 col-md-4 col-lg-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-funnel-fill"></i>
                        </button>
                        <a href="{{ route('scanout.dashboard') }}" class="btn btn-outline-secondary flex-fill" title="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
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
        <nav id="sectionNavCollapse" style="font-size: 0.80rem;" class="nav nav-pills collapse flex-column flex-md-row flex-md-nowrap overflow-auto gap-2 bg-white p-2 rounded-3 shadow-sm section-nav">
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link active" href="#section-summary" data-section="section-summary">Ringkasan</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-trends" data-section="section-trends">Tren Scan Out, Customer &amp; Code Item</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-outgoing-pic" data-section="section-outgoing-pic">Outgoing Type &amp; PIC Scan</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-line-chart" data-section="section-line-chart">Line Chart</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-top10" data-section="section-top10">Top 10 Customer &amp; Code Item</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-detail-data" data-section="section-detail-data">Detail Data</a>
        </nav>
    </div>

    {{-- ===================== SUMMARY CARDS ===================== --}}
    <div class="row g-2 mb-4" id="section-summary">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Scan Out</div>
                            <div class="stat-value text-primary">{{ number_format($totalScanOut) }}</div>
                        </div>
                        <i class="bi bi-upc-scan stat-icon text-primary"></i>
                    </div>
                    <div class="small text-muted mt-1">Jumlah baris/transaksi scan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Customer</div>
                            <div class="stat-value text-danger">{{ number_format($totalCustomer) }}</div>
                        </div>
                        <i class="bi bi-building-fill stat-icon text-danger"></i>
                    </div>
                    <div class="small text-muted mt-1">Customer (unik)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Code Item</div>
                            <div class="stat-value text-success">{{ number_format($totalCodeItem) }}</div>
                        </div>
                        <i class="bi bi-box-seam stat-icon text-success"></i>
                    </div>
                    <div class="small text-muted mt-1">Code Item (unik)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Barcode</div>
                            <div class="stat-value text" style="color: #fd7e14">{{ number_format($totalBarcode) }}</div>
                        </div>
                        <i class="bi bi-upc stat-icon text" style="color: #fd7e14"></i>
                    </div>
                    <div class="small text-muted mt-1">Barcode (unik)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Outgoing</div>
                            <div class="stat-value text-info">{{ number_format($totalOutgoing) }}</div>
                        </div>
                        <i class="bi bi-truck stat-icon text-info"></i>
                    </div>
                    <div class="small text-muted mt-1">No. Outgoing (unik)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total PIC Scan</div>
                            <div class="stat-value text-primary">{{ number_format($totalPic) }}</div>
                        </div>
                        <i class="bi bi-person-badge-fill stat-icon text-primary"></i>
                    </div>
                    <div class="small text-muted mt-1">PIC (unik)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Qty Scan Out</div>
                            <div class="stat-value text-success">{{ number_format((float) $totalQty) }}</div>
                        </div>
                        <i class="bi bi-boxes stat-icon text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted medium">Outgoing per Tipe</div>
                        </div>
                        <i class="bi bi-diagram-3-fill stat-icon text-primary"></i>
                    </div>
                    <div class="medium mt-1">
                        @forelse ($outgoingTypeCount as $type => $count)
                            <span class="badge bg-primary me-1 mb-1">{{ $type }}: {{ number_format($count) }}</span>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== GRAFIK TREN ===================== --}}
    <div class="card table-card mb-4" id="section-trends">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><i class="bi bi-graph-up-arrow text-primary"></i> Grafik Tren Scan Out, Customer &amp; Code Item</span>
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
                    <div class="col-12 col-lg-3">
                        <h6 class="text-muted small text-uppercase mb-2">Jumlah Scan Out</h6>
                        <div class="chart-box" style="position: relative; height: 300px; padding-top: 8px;">
                            <canvas id="chartScanOut"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-lg-3">
                        <h6 class="text-muted small text-uppercase mb-2">Jumlah Customer</h6>
                        <div class="chart-box" style="position: relative; height: 300px; padding-top: 8px;">
                            <canvas id="chartCustomer"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-lg-3">
                        <h6 class="text-muted small text-uppercase mb-2">Jumlah Code Item</h6>
                        <div class="chart-box" style="position: relative; height: 300px; padding-top: 8px;">
                            <canvas id="chartCodeItem"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-lg-3">
                        <h6 class="text-muted small text-uppercase mb-2">Total Qty Scan Out</h6>
                        <div class="chart-box" style="position: relative; height: 300px; padding-top: 8px;">
                            <canvas id="chartQty"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== OUTGOING TYPE (PIE) & PIC SCAN (BAR) ===================== --}}
    <div class="card table-card mb-4" id="section-outgoing-pic">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-pie-chart-fill text-danger"></i> Grafik Outgoing Type &amp; Scan Out per PIC
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <h6 class="text-muted small text-uppercase mb-2 text-center">Jumlah Outgoing berdasarkan Tipe</h6>
                    <div class="chart-box" style="position: relative; height: 320px;">
                        <canvas id="chartOutgoingType"></canvas>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <h6 class="text-muted small text-uppercase mb-2 text-center">Jumlah Item di-Scan Out per PIC</h6>
                    <div class="chart-box" style="position: relative; height: 320px;">
                        <canvas id="chartPicScan"></canvas>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row g-4">
                <div class="col-12 col-lg-6 mx-lg-auto" id="section-line-chart">
                    <h6 class="text-muted small text-uppercase mb-2 text-center">Proporsi Scan Out per Line</h6>
                    <div class="chart-box" style="position: relative; height: 340px;">
                        <canvas id="chartLine"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

   

    {{-- ===================== TOP 10 CUSTOMER & CODE ITEM ===================== --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card table-card h-100" id="section-top10">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-people-fill text-primary"></i> Top 10 Customer (Jumlah Scan Out)
                </div>
               
                <div class="table-responsive" style="max-height: 20rem; overflow-x: auto; overflow-y: auto; display: block; font-size: 0.80rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle">
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Customer</th>
                            <th class="text-end">Jumlah Scan</th>
                            <th class="text-end">Jumlah Barcode</th>
                            <th class="text-end">Code Item</th>
                            <th class="text-end">Total Qty</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topCustomers as $customer)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $customer->customer_name }}</td>
                                <td class="text-end">{{ number_format($customer->jumlah_scan) }}</td>
                                <td class="text-end">{{ number_format($customer->jumlah_barcode) }}</td>
                                <td class="text-end">{{ number_format($customer->jumlah_code_item) }}</td>
                                <td class="text-end">{{ number_format((float) $customer->total_qty) }}</td>
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
                    <i class="bi bi-box-seam text-success"></i> Top 10 Code Item (Jumlah Scan Out)
                </div>
                
                <div class="table-responsive" style="max-height: 20rem; overflow-x: auto; overflow-y: auto; display: block; font-size: 0.75rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle">
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Code Item</th>
                            <th>Customer</th>
                            <th class="text-end">Jumlah Scan</th>
                            <th class="text-end">Jumlah Barcode</th>
                            <th class="text-end">Total Qty</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topCodeItems as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $item->code_item }}</td>
                                <td>{{ $item->customer_name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item->jumlah_scan) }}</td>
                                <td class="text-end">{{ number_format($item->jumlah_barcode) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_qty) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data code item untuk filter ini.</td>
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
        <div class="card-header bg-white fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-table"></i> Detail Data ({{ number_format($rows->total()) }} baris sesuai filter)</span>
            <a href="{{ $exportUrl }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
            </a>
        </div>
        <div class="table-responsive" style="max-height: 65vh; font-size: 0.50rem;">
            <table class="table table-sm table-hover table-striped mb-0 align-middle">
                <thead class="sticky-th">
                <tr>
                    <th>No.</th>
                    <th>Tanggal Scan</th>
                    <th>Row/Lokasi</th>
                    <th>Line</th>
                    <th>Customer</th>
                    <th>Code Item</th>
                    <th>Part No.</th>
                    <th>Part Name</th>
                    <th>Model</th>
                    <th>Barcode</th>
                    <th>NIK Scan</th>
                    <th>PIC Scan</th>
                    <th>Outgoing No</th>
                    <th>Customer To</th>
                    <th>Outgoing Type</th>
                    <th class="text-end">Qty</th>
                    <th>Unit</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>
                        <td class="text-nowrap">{{ $row->scan_date?->format('d-m-Y H:i:s') }}</td>
                        <td class="text-nowrap">{{ $row->row_location }}</td>
                        <td class="text-nowrap">{{ $row->rowLocationMaster->line_label ?? '-' }}</td>
                        <td>{{ $row->customer_name }}</td>
                        <td>{{ $row->code_item }}</td>
                        <td>{{ $row->part_no }}</td>
                        <td>{{ $row->part_name }}</td>
                        <td>{{ $row->model }}</td>
                        <td class="text-nowrap">{{ $row->barcode }}</td>
                        <td>{{ $row->scan_by_nik }}</td>
                        <td>{{ $row->scan_by_name }}</td>
                        <td class="text-nowrap">{{ $row->outgoing_no }}</td>
                        <td>{{ $row->customer_to }}</td>
                        <td class="text-center">
                            @if ($row->outgoing_type === 'Regular')
                                <span class="badge bg-success">{{ $row->outgoing_type }}</span>
                            @elseif ($row->outgoing_type)
                                <span class="badge bg-secondary">{{ $row->outgoing_type }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $row->qty) }}</td>
                        <td>{{ $row->unit ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="17" class="text-center text-muted py-4">Tidak ada data untuk filter ini.</td>
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
        const outgoingTypeChartData = @json($outgoingTypeChartData);
        const picScanChartData = @json($picScanChartData);
        const lineChartData = @json($lineChartData);
        const topCustomerChartData = @json($topCustomerChartData);
        const topCodeItemChartData = @json($topCodeItemChartData);

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
        let chartScanOut, chartCustomer, chartCodeItem, chartQty;
        let chartOutgoingType, chartPicScan, chartTopCustomer, chartTopCodeItem, chartLine;

        function buildTrendCharts(period) {
            const d = chartData[period];
            const labels = d.labels;

            if (chartScanOut) chartScanOut.destroy();
            if (chartCustomer) chartCustomer.destroy();
            if (chartCodeItem) chartCodeItem.destroy();
            if (chartQty) chartQty.destroy();

            const ctxScanOut = document.getElementById('chartScanOut');
            const ctxCustomer = document.getElementById('chartCustomer');
            const ctxCodeItem = document.getElementById('chartCodeItem');
            const ctxQty = document.getElementById('chartQty');
            if (!ctxScanOut || !ctxCustomer || !ctxCodeItem || !ctxQty) return;

            chartScanOut = new Chart(ctxScanOut, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Jumlah Scan Out',
                        data: d.scan_out,
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
                options: { ...commonOptions, layout: { padding: { top: 16, bottom: 8 } } },
            });

            chartCodeItem = new Chart(ctxCodeItem, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Jumlah Code Item',
                        data: d.code_item,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.16)',
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
                options: { ...commonOptions, layout: { padding: { top: 16, bottom: 8 } } },
            });

            chartQty = new Chart(ctxQty, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Total Qty Scan Out',
                        data: d.qty,
                        backgroundColor: '#fd7e14',
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
        }

        function buildOutgoingTypeChart() {
            const ctx = document.getElementById('chartOutgoingType');
            if (!ctx || !outgoingTypeChartData.labels?.length) return;

            const total = outgoingTypeChartData.values.reduce((a, b) => a + b, 0);
            const palette = ['#0d6efd', '#fd7e14', '#198754', '#dc3545', '#6f42c1', '#20c997', '#6c757d'];

            chartOutgoingType = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: outgoingTypeChartData.labels,
                    datasets: [{
                        data: outgoingTypeChartData.values,
                        backgroundColor: outgoingTypeChartData.labels.map((_, i) => palette[i % palette.length]),
                        borderColor: '#fff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 12 },
                            formatter: (value) => {
                                if (!total) return '';
                                const pct = (value / total) * 100;
                                return value > 0 ? value + ' (' + pct.toFixed(0) + '%)' : '';
                            },
                        },
                    },
                },
            });
        }

        function buildPicScanChart() {
            const ctx = document.getElementById('chartPicScan');
            if (!ctx || !picScanChartData.labels?.length) return;

            chartPicScan = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: picScanChartData.labels,
                    datasets: [{
                        label: 'Jumlah Scan Out',
                        data: picScanChartData.values,
                        backgroundColor: '#0dcaf0',
                        borderRadius: 4,
                        datalabels: {
                            anchor: 'end',
                            align: 'right',
                            formatter: (value) => Number(value).toLocaleString('id-ID'),
                        },
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, datalabels: { clip: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }

        function buildLineChart() {
            const ctx = document.getElementById('chartLine');
            if (!ctx || !lineChartData.labels?.length) return;

            const total = lineChartData.values.reduce((a, b) => a + b, 0);
            const palette = [
                '#0d6efd', '#fd7e14', '#198754', '#dc3545', '#6f42c1', '#20c997', '#ffc107',
                '#0dcaf0', '#d63384', '#6610f2', '#adb5bd', '#795548', '#8bc34a', '#607d8b', '#e91e63', '#6c757d',
            ];

            chartLine = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: lineChartData.labels,
                    datasets: [{
                        data: lineChartData.values,
                        backgroundColor: lineChartData.labels.map((_, i) => palette[i % palette.length]),
                        borderColor: '#fff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 11 },
                            formatter: (value) => {
                                if (!total) return '';
                                const pct = (value / total) * 100;
                                return value > 0 ? pct.toFixed(0) + '%' : '';
                            },
                        },
                    },
                },
            });
        }

        

        function buildTopCharts() {
            const ctxTopCustomer = document.getElementById('chartTopCustomer');
            if (ctxTopCustomer && topCustomerChartData.labels?.length) {
                chartTopCustomer = new Chart(ctxTopCustomer, {
                    type: 'bar',
                    data: {
                        labels: topCustomerChartData.labels,
                        datasets: [{
                            label: 'Jumlah Scan Out',
                            data: topCustomerChartData.values,
                            backgroundColor: '#0d6efd',
                            borderRadius: 4,
                            datalabels: {
                                anchor: 'end',
                                align: 'right',
                                formatter: (value) => Number(value).toLocaleString('id-ID'),
                            },
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, datalabels: { clip: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
            }

            const ctxTopCodeItem = document.getElementById('chartTopCodeItem');
            if (ctxTopCodeItem && topCodeItemChartData.labels?.length) {
                chartTopCodeItem = new Chart(ctxTopCodeItem, {
                    type: 'bar',
                    data: {
                        labels: topCodeItemChartData.labels,
                        datasets: [{
                            label: 'Jumlah Scan Out',
                            data: topCodeItemChartData.values,
                            backgroundColor: '#198754',
                            borderRadius: 4,
                            datalabels: {
                                anchor: 'end',
                                align: 'right',
                                formatter: (value) => Number(value).toLocaleString('id-ID'),
                            },
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, datalabels: { clip: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
            }
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
                buildTrendCharts(currentPeriod);
            });
        });

        if (document.getElementById('chartScanOut')) {
            buildTrendCharts(currentPeriod);
        }

        buildOutgoingTypeChart();
        buildPicScanChart();
        buildLineChart();
        buildTopCharts();
    </script>
@endsection
