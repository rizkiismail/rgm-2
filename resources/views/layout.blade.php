<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Monitoring Receiving Goods')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-brand { font-weight: 600; }
        .stat-card { border: none; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); height: 100%; }
        .stat-card .stat-value { font-size: 1.9rem; font-weight: 700; }
        .stat-card .stat-icon { font-size: 1.6rem; opacity: .85; }
        .table-card { border: none; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .filter-card { border: none; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        thead.sticky-th th { position: sticky; top: 0; background: #212529; color: #fff; z-index: 1; }
        .badge-pic { font-size: .8rem; }
        /* Fix: <canvas> is inline by default, leaving a few px of baseline
           whitespace below it. Chart.js's resize-observer then sees the parent
           "grow", resizes the chart again, and the loop repeats forever unless
           the canvas is forced to display:block. */
        .chart-box canvas { display: block; width: 100% !important; height: 100% !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="bi bi-box-seam-fill me-1"></i> Monitoring Receiving Goods
        </a>
        <span class="navbar-text text-white-50 d-none d-md-inline">PT Karya Putra Sangkuriang &mdash; Dept Warehouse</span>
        <div class="ms-auto">
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-light me-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="{{ route('import.form') }}" class="btn btn-sm btn-warning"><i class="bi bi-upload"></i> Upload Data</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 pb-5">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
