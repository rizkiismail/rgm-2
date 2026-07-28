@extends('layout')

@section('title', 'Dashboard - Monitoring Balance Retur')

@section('content')

    @if (!$hasData)
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-1"></i>
            Belum ada data. Silakan <a href="{{ route('retur.import.form') }}" class="alert-link">upload file export Balance Retur</a> terlebih dahulu.
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
            <form method="GET" action="{{ route('retur.dashboard') }}" class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Tanggal Dari</label>
                    <input type="date" style="font-size: 0.80rem;" name="date_from" class="form-control"
                           value="{{ $filters['date_from'] ?? ($dateFrom?->format('Y-m-d')) }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Tanggal Sampai</label>
                    <input type="date" style="font-size: 0.80rem;" name="date_to" class="form-control"
                           value="{{ $filters['date_to'] ?? ($dateTo?->format('Y-m-d')) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Customer</label>
                    <select name="customer"  class="form-select" style="font-size: 0.80rem;">
                        <option value="">Semua Customer</option>
                        @foreach ($customerOptions as $c)
                            <option value="{{ $c }}" @selected(($filters['customer'] ?? '') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Final Status</label>
                    <select name="final_status" class="form-select" style="font-size: 0.80rem;">
                        <option value="">Semua Status</option>
                        <option value="CLOSE" @selected(($filters['final_status'] ?? '') === 'CLOSE')>CLOSE</option>
                        <option value="OPEN" @selected(($filters['final_status'] ?? '') === 'OPEN')>OPEN</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Cari (No Retur / Code Item / Part)</label>
                    <input type="text" style="font-size: 0.80rem;" name="q" class="form-control" placeholder="Ketik kata kunci..."
                           value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="col-12 col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i></button>
                    <a href="{{ route('retur.dashboard') }}" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
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
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-trends" data-section="section-trends">Tren Retur, Customer &amp; Code Item</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-status" data-section="section-status">Status Receiving, Delivery &amp; Final</a>
            <a class="nav-link btn btn-sm btn-outline-primary section-nav-link" href="#section-top10" data-section="section-top10">Top 10 Customer, Code Item &amp; PIC Delivery</a>
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
                            <div class="text-muted small">Total Retur</div>
                            <div class="stat-value text-primary">{{ number_format($totalRetur) }}</div>
                        </div>
                        <i class="bi bi-arrow-return-left stat-icon text-primary"></i>
                    </div>
                    <div class="small text-muted mt-1">No. Retur (unik)</div>
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
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Code Item (Unik)</div>
                            <div class="stat-value text-success">{{ number_format($totalCodeItem) }}</div>
                        </div>
                        <i class="bi bi-upc-scan stat-icon text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Total Qty Retur</div>
                            <div class="stat-value text" style="color : #fd7e14">{{ number_format((float) $totalQtyRetur) }}</div>
                        </div>
                        <i class="bi bi-boxes stat-icon text" style="color : #fd7e14"></i>
                    </div>
                    <div class="small text-muted mt-1">{{ number_format($totalRows) }} baris data</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Qty Receiving Part</div>
                            <div class="stat-value text-info">{{ number_format((float) $totalQtyReceivingPart) }}</div>
                        </div>
                        <i class="bi bi-box-arrow-in-down stat-icon text-info"></i>
                    </div>
                    <div class="small mt-1">
                        <span class="badge bg-success">CLOSE {{ number_format($receivingStatusCount['CLOSE']) }}</span>
                        <span class="badge bg-danger">OPEN {{ number_format($receivingStatusCount['OPEN']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Qty Pending Receiving Part</div>
                            <div class="stat-value text-warning">{{ number_format((float) $totalQtyPendingReceivingPart) }}</div>
                        </div>
                        <i class="bi bi-hourglass-split stat-icon text-warning"></i>
                    </div>
                    <div class="small text-muted mt-1">Belum selesai receiving</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Qty Delivery Part</div>
                            <div class="stat-value text-info">{{ number_format((float) $totalQtyDeliveryPart) }}</div>
                        </div>
                        <i class="bi bi-box-arrow-up stat-icon text-info"></i>
                    </div>
                    <div class="small mt-1">
                        <span class="badge bg-success">CLOSE {{ number_format($deliveryStatusCount['CLOSE']) }}</span>
                        <span class="badge bg-danger">OPEN {{ number_format($deliveryStatusCount['OPEN']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Qty Pending Delivery Part</div>
                            <div class="stat-value text-warning">{{ number_format((float) $totalQtyPendingDeliveryPart) }}</div>
                        </div>
                        <i class="bi bi-clock-history stat-icon text-warning"></i>
                    </div>
                    <div class="small text-muted mt-1">Belum selesai delivery</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Final Status: CLOSE</div>
                            <div class="stat-value text-success">{{ number_format($finalStatusCount['CLOSE']) }}</div>
                        </div>
                        <i class="bi bi-check-circle-fill stat-icon text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Final Status: OPEN</div>
                            <div class="stat-value text-danger">{{ number_format($finalStatusCount['OPEN']) }}</div>
                        </div>
                        <i class="bi bi-exclamation-circle-fill stat-icon text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Persentase Qty receiving Part</div>
                            <div class="stat-value text-info">{{ number_format($receivingPercentage, 2, ',', '.') }}%</div>
                        </div>
                        <i class="bi bi-box-arrow-in-down stat-icon text-info"></i>
                    </div>
                    <div class="small text-muted mt-1">dari Total Qty Retur</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Persentase Qty Delivery Part</div>
                            <div class="stat-value text-info">{{ number_format($deliveryPercentage, 2, ',', '.') }}%</div>
                        </div>
                        <i class="bi bi-box-arrow-up stat-icon text-info"></i>
                    </div>
                    <div class="small text-muted mt-1">dari Total Qty Retur</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== GRAFIK TREN ===================== --}}
    <div class="card table-card mb-4" id="section-trends">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><i class="bi bi-graph-up-arrow text-primary"></i> Grafik Tren Retur, Customer &amp; Code Item</span>
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
                        <h6 class="text-muted small text-uppercase mb-2">Jumlah Retur</h6>
                        <div class="chart-box" style="position: relative; height: 300px; padding-top: 8px;">
                            <canvas id="chartRetur"></canvas>
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
                        <h6 class="text-muted small text-uppercase mb-2">Total Qty Retur</h6>
                        <div class="chart-box" style="position: relative; height: 300px; padding-top: 8px;">
                            <canvas id="chartQtyRetur"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== GRAFIK STATUS ===================== --}}
    <div class="card table-card mb-4" id="section-status">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-pie-chart-fill text-danger"></i> Grafik Status Receiving, Delivery &amp; Final Status
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <h6 class="text-muted small text-uppercase mb-2 text-center">Status Receiving Part</h6>
                    <div class="chart-box" style="position: relative; height: 280px;">
                        <canvas id="chartStatusReceiving"></canvas>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <h6 class="text-muted small text-uppercase mb-2 text-center">Status Delivery Part</h6>
                    <div class="chart-box" style="position: relative; height: 280px;">
                        <canvas id="chartStatusDelivery"></canvas>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <h6 class="text-muted small text-uppercase mb-2 text-center">Final Status</h6>
                    <div class="chart-box" style="position: relative; height: 280px;">
                        <canvas id="chartFinalStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== TOP 10 ===================== --}}

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card table-card h-100" id="section-top10">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-people-fill text-primary"></i> Top 10 Customer
                </div>
                <div class="table-responsive top10-table-wrap" data-visible-rows="10" style="font-size: 0.55rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle">
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Customer</th>
                            <th class="text-end">Jumlah Retur</th>
                            <th class="text-end">Total Qty Retur</th>
                            <th class="text-end">Total Qty Receiving</th>
                            <th class="text-end">Total Qty Delivery</th>
                            <th class="text-center">Open</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topCustomers as $customer)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $customer->customer_name }}</td>
                                <td class="text-end">{{ number_format($customer->jumlah_retur) }}</td>
                                <td class="text-end">{{ number_format((float) $customer->total_qty_retur) }}</td>
                                <td class="text-end">{{ number_format((float) $customer->total_qty_receiving) }}</td>
                                <td class="text-end">{{ number_format((float) $customer->total_qty_delivery) }}</td>
                                <td class="text-center">
                                    @if ($customer->open_count > 0)
                                        <span class="badge bg-danger">{{ $customer->open_count }}</span>
                                    @else
                                        <span class="badge bg-success">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada data customer untuk filter ini.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card table-card h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-box-seam text-success"></i> Top 10 Code Item
                </div>
                <div class="table-responsive top10-table-wrap" data-visible-rows="10" style="font-size: 0.5386rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle" >
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Code Item</th>
                            <th>Customer</th>
                            <th class="text-end">Jumlah Retur</th>
                            <th class="text-end">Total Qty Retur</th>
                            <th class="text-center">Open</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topCodeItems as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $item->code_item }}</td>
                                <td>{{ $item->customer_name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item->jumlah_retur) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_qty_retur) }}</td>
                                <td class="text-center">
                                    @if ($item->open_count > 0)
                                        <span class="badge bg-danger">{{ $item->open_count }}</span>
                                    @else
                                        <span class="badge bg-success">0</span>
                                    @endif
                                </td>
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

        <div class="col-12 col-xl-4" >
            <div class="card table-card h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-person-badge-fill text-info"></i> Top 10 PIC PPIC Delivery
                </div>
                <div class="table-responsive top10-table-wrap" data-visible-rows="10" style="font-size: 0.70rem;">
                    <table class="table table-sm table-hover table-striped mb-0 align-middle" >
                        <thead class="sticky-th">
                        <tr>
                            <th class="text-center">No</th>
                            <th>PIC PPIC Delivery</th>
                            <th class="text-end">Jumlah Retur</th>
                            <th class="text-end">Total Qty Delivery</th>
                            <th class="text-center">Close</th>
                            <th class="text-center">Open</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($topPicDelivery as $pic)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $pic->pic_ppic_delivery }}</td>
                                <td class="text-end">{{ number_format($pic->jumlah_retur) }}</td>
                                <td class="text-end">{{ number_format((float) $pic->total_qty_delivery) }}</td>
                                <td class="text-center"><span class="badge bg-success">{{ $pic->close_count }}</span></td>
                                <td class="text-center">
                                    @if ($pic->open_count > 0)
                                        <span class="badge bg-danger">{{ $pic->open_count }}</span>
                                    @else
                                        <span class="badge bg-secondary">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data PIC PPIC Delivery untuk filter ini.</td>
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
                    <th>Tanggal Retur</th>
                    <th>No. Retur</th>
                    <th>No. Revisi</th>
                    <th>No. From Customer</th>
                    <th>Customer</th>
                    <th>Code Item</th>
                    <th>Part Nomer</th>
                    <th>Part Name</th>
                    <th>Model</th>
                    <th>Product Status</th>
                    <th class="text-end">Qty Retur</th>
                    <th>Unit</th>
                    <th class="text-end">Qty Receiving</th>
                    <th class="text-end">Qty Pending Receiving</th>
                    <th class="text-center">Status Receiving</th>
                    <th class="text-end">Qty Delivery</th>
                    <th class="text-end">Qty Pending Delivery</th>
                    <th class="text-center">Status Delivery</th>
                    <th class="text-center">Stock Realtime</th>
                    <th class="text-center">Final Status</th>
                    <th class="text-center">Note</th>
                    <th>PIC PPIC Delivery</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>
                        <td class="text-nowrap">{{ $row->date_retur?->format('d-m-Y') }}</td>
                        <td class="text-nowrap">{{ $row->no_retur }}</td>
                        <td>{{ $row->rev_no }}</td>
                        <td class="text-nowrap">{{ $row->no_from_customer }}</td>
                        <td>{{ $row->customer_name }}</td>
                        <td>{{ $row->code_item }}</td>
                        <td>{{ $row->part_no }}</td>
                        <td>{{ $row->part_name }}</td>
                        <td>{{ $row->model }}</td>
                        <td>{{ $row->product_status }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_retur) }}</td>
                        <td>{{ $row->unit }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_receiving_part) }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_pending_receiving_part) }}</td>
                        <td class="text-center">
                            @if ($row->status_receiving === 'CLOSE')
                                <span class="badge bg-success">CLOSE</span>
                            @elseif ($row->status_receiving === 'OPEN')
                                <span class="badge bg-secondary">OPEN</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $row->qty_delivery_part) }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_pending_delivery_part) }}</td>
                        <td class="text-center">
                            @if ($row->status_delivery === 'CLOSE')
                                <span class="badge bg-success">CLOSE</span>
                            @elseif ($row->status_delivery === 'OPEN')
                                <span class="badge bg-secondary">OPEN</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>{{ $row->stock_realtime ?? '-' }}</td>
                        <td class="text-center">
                            @if ($row->final_status === 'CLOSE')
                                <span class="badge bg-success">CLOSE</span>
                            @elseif ($row->final_status === 'OPEN')
                                <span class="badge bg-danger">OPEN</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>{{ $row->note ?? '-' }}</td>
                        <td>{{ $row->pic_ppic_delivery ?? '-' }}</td>
                        
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">Tidak ada data untuk filter ini.</td>
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
        const statusChartData = @json($statusChartData);
        const topCustomerChartData = @json($topCustomerChartData);
        const topCodeItemChartData = @json($topCodeItemChartData);
        const topPicDeliveryChartData = @json($topPicDeliveryChartData);

        function formatLabels(labels) {
            return labels;
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
        let chartRetur, chartCustomer, chartCodeItem, chartQtyRetur;
        let chartStatusReceiving, chartStatusDelivery, chartFinalStatus;
        let chartTopCustomer, chartTopCodeItem, chartTopPicDelivery;

        function buildTrendCharts(period) {
            const d = chartData[period];
            const labels = formatLabels(d.labels);

            if (chartRetur) chartRetur.destroy();
            if (chartCustomer) chartCustomer.destroy();
            if (chartCodeItem) chartCodeItem.destroy();
            if (chartQtyRetur) chartQtyRetur.destroy();

            const ctxRetur = document.getElementById('chartRetur');
            const ctxCustomer = document.getElementById('chartCustomer');
            const ctxCodeItem = document.getElementById('chartCodeItem');
            const ctxQtyRetur = document.getElementById('chartQtyRetur');
            if (!ctxRetur || !ctxCustomer || !ctxCodeItem || !ctxQtyRetur) return;

            chartRetur = new Chart(ctxRetur, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Jumlah Retur',
                        data: d.retur,
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

            chartQtyRetur = new Chart(ctxQtyRetur, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Total Qty Retur',
                        data: d.qty_retur,
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

        function buildStatusDoughnut(canvasId, data, colors) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            const total = data.values.reduce((a, b) => a + b, 0);

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: colors,
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

        function buildTopCharts() {
            const ctxTopCustomer = document.getElementById('chartTopCustomer');
            if (ctxTopCustomer && topCustomerChartData.labels?.length) {
                if (chartTopCustomer) chartTopCustomer.destroy();
                chartTopCustomer = new Chart(ctxTopCustomer, {
                    type: 'bar',
                    data: {
                        labels: topCustomerChartData.labels,
                        datasets: [{
                            label: 'Total Qty Retur',
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
                if (chartTopCodeItem) chartTopCodeItem.destroy();
                chartTopCodeItem = new Chart(ctxTopCodeItem, {
                    type: 'bar',
                    data: {
                        labels: topCodeItemChartData.labels,
                        datasets: [{
                            label: 'Total Qty Retur',
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

            const ctxTopPicDelivery = document.getElementById('chartTopPicDelivery');
            if (ctxTopPicDelivery && topPicDeliveryChartData.labels?.length) {
                if (chartTopPicDelivery) chartTopPicDelivery.destroy();
                chartTopPicDelivery = new Chart(ctxTopPicDelivery, {
                    type: 'bar',
                    data: {
                        labels: topPicDeliveryChartData.labels,
                        datasets: [{
                            label: 'Total Qty Delivery',
                            data: topPicDeliveryChartData.values,
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

        if (document.getElementById('chartRetur')) {
            buildTrendCharts(currentPeriod);
        }

        chartStatusReceiving = buildStatusDoughnut('chartStatusReceiving', statusChartData.receiving, ['#198754', '#6c757d']);
        chartStatusDelivery = buildStatusDoughnut('chartStatusDelivery', statusChartData.delivery, ['#198754', '#6c757d']);
        chartFinalStatus = buildStatusDoughnut('chartFinalStatus', statusChartData.final, ['#198754', '#dc3545']);

        buildTopCharts();
    </script>
@endsection
