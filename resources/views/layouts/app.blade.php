<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AmLux Travel Services')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #0a2a6b; --red: #e31e2d; }
        .navbar { background: var(--navy); }
        .btn-primary { background: var(--navy); border-color: var(--navy); }
        .btn-danger { background: var(--red); border-color: var(--red); }
        .card-header { background: var(--navy); color: #fff; }
        .brand small { display: block; font-size: .65rem; font-style: italic; opacity: .8; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand brand" href="{{ route('flights.index') }}">AmLux Travel Services<small>Indulge in luxury experiences</small></a>
        <div class="navbar-nav">
            <a class="nav-link" href="{{ route('customers.index') }}">Customers</a>
            <a class="nav-link" href="{{ route('flights.index') }}">Flights</a>
            <a class="nav-link" href="{{ route('flights.create') }}">New Booking</a>
        </div>
    </div>
</nav>
<div class="container pb-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</div>
</body>
</html>
