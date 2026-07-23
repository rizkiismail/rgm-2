<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Monitoring Receiving Goods')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            color-scheme: light;
            --body-bg: #f4f6f9;
            --body-color: #212529;
            --card-bg: #ffffff;
            --card-color: #212529;
            --card-header-bg: #ffffff;
            --surface-bg: #f8f9fa;
            --border-color: #e9ecef;
            --input-bg: #ffffff;
            --input-color: #212529;
            --muted-color: #6c757d;
            --table-header-bg: #212529;
            --table-header-color: #ffffff;
            --navbar-bg: #212529;
            --dropdown-bg: #ffffff;
            --dropdown-color: #212529;
            --shadow-color: rgba(0,0,0,.06);
            --navbar-h: 62px;
            --filter-h: 0px;
        }
        html[data-theme="dark"] {
            color-scheme: dark;
            --body-bg: #0f172a;
            --body-color: #e5e7eb;
            --card-bg: #111827;
            --card-color: #f9fafb;
            --card-header-bg: #1f2937;
            --surface-bg: #1f2937;
            --border-color: #374151;
            --input-bg: #1f2937;
            --input-color: #f9fafb;
            --muted-color: #94a3b8;
            --table-header-bg: #1f2937;
            --table-header-color: #f9fafb;
            --navbar-bg: #030712;
            --dropdown-bg: #111827;
            --dropdown-color: #f9fafb;
            --shadow-color: rgba(2,6,23,.45);
        }

        html { scroll-behavior: smooth; }
        body {
            background-color: var(--body-bg);
            color: var(--body-color);
            transition: background-color .2s ease, color .2s ease;
        }
        .navbar-brand { font-weight: 600; }
        .dropdown-menu {
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 20px rgba(0,0,0,.08);
            background-color: var(--dropdown-bg);
        }
        .dropdown-item {
            color: var(--dropdown-color);
            background-color: transparent;
        }
        .dropdown-item:hover,
        .dropdown-item:focus,
        .dropdown-item.active {
            background-color: transparent;
            color: #0d6efd;
        }
        .stat-card {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 2px 10px var(--shadow-color);
            height: 100%;
            background-color: var(--card-bg);
            color: var(--card-color);
        }
        .stat-card .stat-value { font-size: 1.9rem; font-weight: 700; }
        .stat-card .stat-icon { font-size: 1.6rem; opacity: .85; }
        .table-card, .filter-card {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 2px 10px var(--shadow-color);
            background-color: var(--card-bg);
            color: var(--card-color);
        }
        .card, .card-header, .card-footer, .card-body {
            color: var(--card-color);
        }
        .card-header, .card-footer {
            background-color: var(--card-header-bg);
            border-color: var(--border-color);
        }
        .card-body {
            background-color: transparent;
        }
        .form-control, .form-select, .form-check-input, .input-group-text {
            background-color: var(--input-bg);
            color: var(--input-color);
            border-color: var(--border-color);
        }
        .form-control::placeholder { color: var(--muted-color); }
        .table, .table > :not(caption) > * > * {
            color: var(--card-color);
            border-color: var(--border-color);
        }
        .table thead th, thead.sticky-th th {
            background-color: var(--table-header-bg);
            color: var(--table-header-color);
        }
        .list-group-item {
            background-color: var(--card-bg);
            color: var(--card-color);
            border-color: var(--border-color);
        }
        .alert-info {
            background-color: var(--surface-bg);
            color: var(--body-color);
            border-color: var(--border-color);
        }
        .badge-pic { font-size: .8rem; }
        .bg-white { background-color: var(--card-bg) !important; }
        .bg-light { background-color: var(--surface-bg) !important; }
        .text-muted { color: var(--muted-color) !important; }
        .text-dark { color: var(--body-color) !important; }
        .navbar-dark {
            background-color: var(--navbar-bg) !important;
        }
        .navbar-brand, .navbar-text, .navbar .btn {
            color: #fff !important;
        }
        .chart-box canvas { display: block; width: 100% !important; height: 100% !important; }

        .app-navbar { position: sticky; top: 0; z-index: 1035; transition: box-shadow .2s ease; }
        .app-navbar.is-scrolled { box-shadow: 0 2px 10px rgba(0,0,0,.25); }
        .filter-card.sticky-filter {
            position: sticky;
            top: var(--navbar-h);
            z-index: 1030;
            background-color: var(--body-bg);
        }
        .filter-card.sticky-filter .card-body {
            background-color: var(--card-bg);
            border-radius: 14px;
        }
        .section-nav {
            position: sticky;
            top: calc(var(--navbar-h) + var(--filter-h));
            z-index: 1020;
            background-color: var(--card-bg);
        }
        .section-nav .nav-link {
            color: var(--body-color);
        }
        .section-nav .nav-link:hover { color: #fff !important; }
        [id^="section-"] { scroll-margin-top: calc(var(--navbar-h) + var(--filter-h) + 70px); }

        #backToTop {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--navbar-bg);
            color: #fff;
            border: none;
            box-shadow: 0 4px 14px rgba(0,0,0,.25);
            font-size: 1.2rem;
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transform: translateY(12px);
            transition: opacity .25s ease, transform .25s ease, background-color .2s ease, visibility .25s;
        }
        #backToTop.show { opacity: 1; visibility: visible; transform: translateY(0); }
        #backToTop:hover { background-color: #000; color: #fff; }
        @media (max-width: 576px) {
            #backToTop { right: 16px; bottom: 16px; width: 42px; height: 42px; font-size: 1.05rem; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 app-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="bi bi-box-seam-fill me-1"></i>
            {{ request()->routeIs('retur.dashboard') ? 'Monitoring Balance Retur' : 'Monitoring Receiving Goods' }}
        </a>
        <span class="navbar-text text-white-50 d-none d-md-inline">PT Karya Putra Sangkuriang &mdash; Dept Warehouse</span>
        <div class="ms-auto d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <button type="button" id="themeToggle" class="btn btn-sm btn-outline-light" aria-pressed="false" title="Ubah tema">
                <i class="bi bi-moon-stars-fill"></i> 
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-sm {{ request()->routeIs(['dashboard', 'retur.dashboard']) ? 'btn-primary' : 'btn-outline-light' }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-folder2 me-2"></i> Receiving Goods
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('retur.dashboard') ? 'active' : '' }}" href="{{ route('retur.dashboard') }}">
                            <i class="bi bi-arrow-return-left me-2"></i> Balance Retur
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('import.form') }}" class="btn btn-sm {{ request()->routeIs('import.form') ? 'btn-secondary' : 'btn-outline-light' }}"><i class="bi bi-upload"></i> Upload Receiving</a>
            <a href="{{ route('retur.import.form') }}" class="btn btn-sm {{ request()->routeIs('retur.import.form') ? 'btn-warning' : 'btn-outline-warning' }}"><i class="bi bi-upload"></i> Upload Retur</a>
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

<footer class="footer py-3 bg-light border-top">
    <div class="container-fluid px-4">
        <div class="text-center small text-muted">© 2026 Rizki Andriana Ismail</div>
    </div>
</footer>

<button type="button" id="backToTop" title="Kembali ke atas" aria-label="Kembali ke atas">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        function updateStickyOffsets() {
            var navbar = document.querySelector('.app-navbar');
            var filter = document.querySelector('.filter-card');
            var navH = navbar ? navbar.offsetHeight : 0;
            var filterH = filter ? filter.offsetHeight : 0;
            document.documentElement.style.setProperty('--navbar-h', navH + 'px');
            document.documentElement.style.setProperty('--filter-h', filterH + 'px');
        }

        function toggleNavbarShadow() {
            var navbar = document.querySelector('.app-navbar');
            if (!navbar) return;
            navbar.classList.toggle('is-scrolled', window.scrollY > 4);
        }

        function toggleBackToTop() {
            var btn = document.getElementById('backToTop');
            if (!btn) return;
            btn.classList.toggle('show', window.scrollY > 300);
        }

        function applyTheme(theme) {
            var root = document.documentElement;
            root.setAttribute('data-theme', theme);
            root.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);

            var toggle = document.getElementById('themeToggle');
            if (toggle) {
                var icon = toggle.querySelector('i');
                var text = toggle.querySelector('span');
                if (icon) {
                    icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
                }
                if (text) {
                    text.textContent = theme === 'dark' ? 'Light' : 'Dark';
                }
                toggle.classList.toggle('btn-outline-light', theme === 'light');
                toggle.classList.toggle('btn-outline-secondary', theme === 'dark');
                toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            }
        }

        var savedTheme = localStorage.getItem('theme');
        var initialTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        applyTheme(initialTheme);

        var backToTopBtn = document.getElementById('backToTop');
        if (backToTopBtn) {
            backToTopBtn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        var themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(currentTheme);
            });
        }

        window.addEventListener('load', updateStickyOffsets);
        window.addEventListener('resize', updateStickyOffsets);
        window.addEventListener('scroll', toggleNavbarShadow, { passive: true });
        window.addEventListener('scroll', toggleBackToTop, { passive: true });
        document.addEventListener('DOMContentLoaded', updateStickyOffsets);

        if (window.ResizeObserver) {
            var filterEl = document.querySelector('.filter-card');
            if (filterEl) {
                new ResizeObserver(updateStickyOffsets).observe(filterEl);
            }
        }
    })();
</script>
@yield('scripts')
</body>
</html>
