<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · AmLux Travel Services</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/amlux.css') }}?v={{ filemtime(public_path('css/amlux.css')) }}">
</head>
<body>
<div class="app">
    <aside class="side">
        <a class="brand" href="{{ route('dashboard') }}" title="AmLux Travel Services"><img src="{{ asset('img/logo.webp') }}" alt="AmLux Travel Services — Indulge in luxury experiences" width="164" height="92"></a>

        <nav class="nav nav-top">
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><x-icon name="house" />Dashboard</a>
        </nav>

        <div class="nav-label">Workspace</div>
        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('flights.*') ? 'active' : '' }}" href="{{ route('flights.index') }}"><x-icon name="plane" />Flight Bookings<span class="badge">{{ $navFlights }}</span></a>
            <a class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><x-icon name="users" />Customers<span class="badge">{{ $navCustomers }}</span></a>
        </nav>

        <div class="nav-sep"></div>
        <nav class="nav nav-bottom">
            <span class="nav-item" title="Coming soon"><x-icon name="chart-line" />Reports</span>
            <span class="nav-item" title="Coming soon"><x-icon name="gear" />Settings</span>
            <span class="nav-item" title="Coming soon"><x-icon name="circle-question" />Support</span>
        </nav>

        <div class="side-foot">
            <img class="globe" src="{{ asset('img/globe.svg') }}" alt="">
            <div class="tag">Your Trusted Travel Partner</div>
            <div class="rule"></div>
        </div>
    </aside>

    <div class="main">
        <header class="hero">
            <form class="search" method="GET" action="{{ route('flights.index') }}" role="search">
                <x-icon name="magnifying-glass" />
                <input type="search" name="q" id="global-search" value="{{ request()->routeIs('flights.index') ? request('q') : '' }}" placeholder="Search PNR, customer or phone…" autocomplete="off">
                <span class="kbd" id="kbd-hint"><i>Ctrl</i><i>K</i></span>
            </form>
            <div class="hero-tools">
                <span class="bell" title="Notifications"><x-icon name="bell" /></span>
                <span class="avatar-btn" title="Account"><x-icon name="user" /></span>
                <span class="today"><x-icon name="calendar-days" />{{ now()->format('D, d M Y') }}</span>
            </div>
            <div class="hero-tag"><b>More Than Just Tickets</b><span>We create journeys</span></div>
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
        if (/Mac|iPhone|iPad/.test(navigator.platform)) k.firstChild.textContent = '⌘';
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); s.focus(); s.select(); }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
