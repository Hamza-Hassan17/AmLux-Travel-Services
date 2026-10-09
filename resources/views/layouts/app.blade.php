<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · AmLux Travel Services</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/amlux.css') }}?v={{ filemtime(public_path('css/amlux.css')) }}">
</head>
<body>
<div class="app">
    <aside class="side">
        <a class="brand" href="{{ route('flights.index') }}">
            <span class="brand-name">AmLux</span>
            <span class="brand-sub">Travel Services · Back office</span>
        </a>
        <div class="nav-label">Workspace</div>
        <nav class="nav">
            <a href="{{ route('flights.index') }}" class="{{ request()->routeIs('flights.*') ? 'active' : '' }}">Flight bookings<span class="count">{{ $navFlights }}</span></a>
            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Customers<span class="count">{{ $navCustomers }}</span></a>
        </nav>
        <div class="side-foot">Indulge in luxury experiences</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <form class="search" method="GET" action="{{ route('flights.index') }}" role="search">
                <input type="search" name="q" id="global-search" value="{{ request()->routeIs('flights.index') ? request('q') : '' }}" placeholder="Search PNR, customer or phone…" autocomplete="off">
                <span class="kbd" id="kbd-hint">Ctrl K</span>
            </form>
            <div class="today">{{ now()->format('D, j M Y') }}</div>
        </header>

        <div class="content">
            @if (session('success'))
                <div class="alert alert-ok">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-err"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
<script>
    (function () {
        var s = document.getElementById('global-search'), k = document.getElementById('kbd-hint');
        if (/Mac|iPhone|iPad/.test(navigator.platform)) k.textContent = '⌘K';
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); s.focus(); s.select(); }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
