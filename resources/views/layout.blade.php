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
        html, body { overflow-x: clip; max-width: 100%; }
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
        .section-nav-wrapper {
            position: sticky;
            top: calc(var(--navbar-h) + var(--filter-h));
            z-index: 1020;
            background-color: var(--body-bg);
        }
        .section-nav {
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
            #backToTop { right: 14px; bottom: 14px; width: 40px; height: 40px; font-size: 1rem; }
        }

        /* ===== Mobile typography: keep text from feeling oversized on small screens ===== */
        @media (max-width: 576px) {
            body { font-size: .92rem; }
            .navbar-brand { font-size: 1rem; }
            .navbar-text { font-size: .78rem; }
            .stat-card .stat-value { font-size: 1.35rem; }
            .stat-card .stat-icon { font-size: 1.2rem; }
            .stat-card .small { font-size: .72rem; }
            .card-header { font-size: .88rem; }
            .section-nav .nav-link,
            .section-nav-link,
            #filterToggle,
            #sectionNavToggle { font-size: .8rem; }
            .btn-sm { font-size: .78rem; padding: .3rem .55rem; }
            .form-label.small { font-size: .72rem; }
            h6.small { font-size: .68rem; }
            .table-responsive { font-size : 0.50rem}
        }

        /* ===== Pagination: never overflow the mobile viewport =====
           Laravel's Bootstrap-4 pagination view renders a single <ul class="pagination">
           with one <li> per page number. With many pages this row is wider than a phone
           screen, so it must be allowed to wrap instead of forcing horizontal overflow. */
        .card-footer { overflow-x: hidden; }
        .pagination {
            flex-wrap: wrap;
            row-gap: .35rem;
            margin-bottom: 0;
        }
        @media (max-width: 576px) {
            .pagination {
                justify-content: center;
                gap: .2rem;
            }
            .pagination .page-item {
                margin: 0;
            }
            .pagination .page-link {
                padding: .3rem .55rem;
                font-size: .78rem;
                min-width: 2.1rem;
                text-align: center;
            }
        }

        /* ===== Responsive navbar / hamburger menus ===== */
        .navbar-toggler {
            border-color: rgba(255,255,255,.35);
        }
        @media (max-width: 991.98px) {
            #navbarContent .btn-group,
            #navbarContent > div > .btn,
            #navbarContent .btn-group .btn {
                width: 100%;
            }
            #navbarContent .dropdown-menu {
                width: 100%;
            }
        }

        /* Filter card hamburger toggle (mobile) */
        #filterToggle { display: none; }
        @media (max-width: 767.98px) {
            #filterToggle { display: flex; }
        }
        .filter-collapse-body { }
        @media (max-width: 767.98px) {
            .filter-collapse-body.collapse:not(.show) { display: none; }
        }
        @media (min-width: 768px) {
            .filter-collapse-body.collapse { display: block !important; height: auto !important; }
        }

        /* Section navlink hamburger toggle (mobile) */
        #sectionNavToggle { display: none; }
        @media (max-width: 767.98px) {
            #sectionNavToggle { display: flex; }
        }
        @media (max-width: 767.98px) {
            .section-nav.collapse:not(.show) { display: none; }
        }
        @media (min-width: 768px) {
            .section-nav.collapse { display: flex !important; height: auto !important; }
        }
        .section-nav.flex-column .nav-link { text-align: left; width: 100%; }

        /* ===== Top 10 tables: always show 10 rows, scroll for the rest ===== */
        .top10-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            display: block;
        }
        .top10-table-wrap thead.sticky-th th { position: sticky; top: 0; z-index: 1; }

    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 app-navbar">
    <div class="container-fluid px-3 px-md-4">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="bi bi-box-seam-fill me-1"></i>
            @if (request()->routeIs('retur.dashboard') || request()->routeIs('retur.import.form'))
                Monitoring Balance Retur
            @elseif (request()->routeIs('scanout.dashboard') || request()->routeIs('scanout.import.form'))
                Monitoring Raking Scan Out
            @else
                Monitoring Receiving Goods
            @endif
        </a>
        <button type="button" id="themeToggle" class="btn btn-sm btn-outline-light order-lg-2 ms-auto ms-lg-0 me-2" aria-pressed="false" title="Ubah tema">
            <i class="bi bi-moon-stars-fill"></i>
        </button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <span class="navbar-text text-white-50 d-block d-lg-inline mt-2 mt-lg-0">PT Karya Putra Sangkuriang &mdash; Dept Warehouse</span>
            <div class="d-flex flex-column flex-lg-row flex-wrap gap-2 ms-lg-auto align-items-stretch align-items-lg-center mt-3 mt-lg-0">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm {{ request()->routeIs(['dashboard', 'retur.dashboard', 'scanout.dashboard']) ? 'btn-primary' : 'btn-outline-light' }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('scanout.dashboard') ? 'active' : '' }}" href="{{ route('scanout.dashboard') }}">
                                <i class="bi bi-upc-scan me-2"></i> Raking Scan Out
                            </a>
                        </li>
                    </ul>
                </div>
      <div class="btn-group">
                    <button type="button" class="btn btn-sm {{ request()->routeIs(['import.form', 'retur.import.form', 'scanout.import.form']) ? 'btn-warning' : 'btn-outline-light' }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-upload"></i> Upload Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('import.form') ? 'active' : '' }}" href="{{ route('import.form') }}">
                                <i class="bi bi-folder2 me-2"></i> Upload Receiving
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('retur.import.form') ? 'active' : '' }}" href="{{ route('retur.import.form') }}">
                                <i class="bi bi-arrow-return-left me-2"></i> Upload Retur
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('scanout.import.form') ? 'active' : '' }}" href="{{ route('scanout.import.form') }}">
                                <i class="bi bi-upc-scan me-2"></i> Upload Scan Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
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

        // ===== Top 10 tables: force exactly 10 visible rows, scroll for the rest =====
        // Height is measured from the actually-rendered header + first row, so this
        // works consistently across desktop and mobile even though each table uses
        // a different font-size per breakpoint.
        function sizeTop10Tables() {
            document.querySelectorAll('.top10-table-wrap').forEach(function (wrap) {
                var table = wrap.querySelector('table');
                if (!table) return;
                var thead = table.querySelector('thead');
                var tbody = table.querySelector('tbody');
                if (!thead || !tbody) return;
                var rows = tbody.querySelectorAll('tr');

                // Reset before re-measuring so old forced heights don't skew the numbers.
                wrap.style.maxHeight = '';
                wrap.style.height = '';

                var visibleRows = parseInt(wrap.getAttribute('data-visible-rows'), 10) || 10;

                // 10 rows or fewer (including the "no data" placeholder row): let it size
                // naturally, no need to force a scrollbar.
                if (rows.length <= visibleRows) {
                    return;
                }

                var theadHeight = thead.getBoundingClientRect().height;
                var rowHeight = rows[0].getBoundingClientRect().height;
                if (!theadHeight || !rowHeight) return;

                wrap.style.maxHeight = Math.ceil(theadHeight + (rowHeight * visibleRows)) + 'px';
            });
        }

        var top10ResizeTimer;
        function scheduleSizeTop10Tables() {
            clearTimeout(top10ResizeTimer);
            top10ResizeTimer = setTimeout(sizeTop10Tables, 150);
        }

        document.addEventListener('DOMContentLoaded', sizeTop10Tables);
        window.addEventListener('load', sizeTop10Tables);
        window.addEventListener('resize', scheduleSizeTop10Tables);

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
            var navbarEl = document.querySelector('.app-navbar');
            if (navbarEl) {
                new ResizeObserver(updateStickyOffsets).observe(navbarEl);
            }
        }

        ['navbarContent', 'filterCollapse', 'sectionNavCollapse'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('shown.bs.collapse', updateStickyOffsets);
            el.addEventListener('hidden.bs.collapse', updateStickyOffsets);
        });

        // Auto-close the mobile "Navigasi Bagian" hamburger menu after a link is tapped
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.section-nav-link');
            if (!link) return;
            var nav = document.getElementById('sectionNavCollapse');
            if (nav && window.innerWidth < 768 && nav.classList.contains('show') && window.bootstrap) {
                var collapseInstance = window.bootstrap.Collapse.getOrCreateInstance(nav);
                collapseInstance.hide();
            }
        });
    })();
</script>
@yield('scripts')
</body>
</html>
