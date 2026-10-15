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

        <div class="nav-label">Transactions</div>
        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><x-icon name="file-invoice" />Invoices<span class="badge">{{ $navInvoices }}</span></a>
            <a class="nav-item {{ request()->routeIs('receipts.*') ? 'active' : '' }}" href="{{ route('receipts.index') }}"><x-icon name="receipt" />Receipts</a>
            <a class="nav-item {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><x-icon name="wallet" />Payments</a>
            <a class="nav-item {{ request()->routeIs('refunds.*') ? 'active' : '' }}" href="{{ route('refunds.index') }}"><x-icon name="rotate-left" />Refunds</a>
        </nav>

        <div class="nav-label">Bookings</div>
        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('flights.*') ? 'active' : '' }}" href="{{ route('flights.index') }}"><x-icon name="plane" />Flights<span class="badge">{{ $navFlights }}</span></a>
            <a class="nav-item {{ request()->routeIs('hotels.*') ? 'active' : '' }}" href="{{ route('hotels.index') }}"><x-icon name="hotel" />Hotels<span class="badge">{{ $navHotels }}</span></a>
        </nav>

        <div class="nav-label">Directory</div>
        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><x-icon name="users" />Customers<span class="badge">{{ $navCustomers }}</span></a>
            <a class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><x-icon name="building" />Suppliers</a>
            <a class="nav-item {{ request()->routeIs('agents.*') ? 'active' : '' }}" href="{{ route('agents.index') }}"><x-icon name="user-group" />Commission agents</a>
            <a class="nav-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}"><x-icon name="building-columns" />Cash &amp; bank</a>
        </nav>

        <div class="nav-sep"></div>
        <nav class="nav nav-bottom">
            <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><x-icon name="chart-line" />Reports</a>
            <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><x-icon name="gear" />Settings</a>
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
                <details class="usermenu" id="usermenu">
                    <summary class="avatar-btn" title="{{ auth()->user()->name }}">{{ auth()->user()->initials }}</summary>
                    <div class="um-pop">
                        <div class="um-head"><b>{{ auth()->user()->name }}</b><span>{{ auth()->user()->email }}</span></div>
                        <a href="{{ route('settings.index') }}"><x-icon name="gear" />Settings</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="right-from-bracket" />Sign out</button></form>
                    </div>
                </details>
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
        document.addEventListener('click', function (e) {
            var m = document.getElementById('usermenu');
            if (m && !m.contains(e.target)) m.removeAttribute('open');
        });
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); s.focus(); s.select(); }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
