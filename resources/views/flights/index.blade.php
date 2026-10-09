@extends('layouts.app')
@section('title', 'Flight bookings')
@section('content')
@php
    $tabs = [
        ['key' => null, 'label' => 'All', 'n' => $counts->sum()],
        ['key' => 'confirmed', 'label' => 'Confirmed', 'n' => $counts->get('confirmed', 0)],
        ['key' => 'pending', 'label' => 'Pending', 'n' => $counts->get('pending', 0)],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'n' => $counts->get('cancelled', 0)],
    ];
    $keep = request()->except('page');
    $active = collect($filters)->only(['q', 'airline', 'payment', 'from'])->filter()->isNotEmpty();
@endphp

<div class="page-head">
    <div>
        <div class="crumbs">Bookings &nbsp;/&nbsp; Flights</div>
        <h1>Flight bookings</h1>
    </div>
    <div class="actions">
        <a class="btn btn-ghost" href="{{ route('flights.export', $keep) }}">Export CSV</a>
        <a class="btn btn-red" href="{{ route('flights.create') }}">+ New flight booking</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi">
        <div class="kpi-label">Bookings this month</div>
        <div class="kpi-val">
            <b>{{ number_format($kpis['month']) }}</b>
            @if (! is_null($kpis['trend']))
                <small class="{{ $kpis['trend'] >= 0 ? 'up' : 'down' }}">{{ $kpis['trend'] >= 0 ? '+' : '' }}{{ $kpis['trend'] }}% vs {{ $kpis['lastMonth'] }}</small>
            @endif
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Revenue this month</div>
        <div class="kpi-val"><span class="unit">PKR</span><b>{{ $kpis['revenue'] }}</b></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Awaiting ticketing</div>
        <div class="kpi-val"><b class="warn">{{ $kpis['pending'] }}</b>@if ($kpis['pendingSoon'])<small>{{ $kpis['pendingSoon'] }} depart in 3 days</small>@endif</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Departing in 7 days</div>
        <div class="kpi-val"><b>{{ $kpis['upcoming'] }}</b></div>
    </div>
</div>

<section class="panel panel-clip">
    <div class="tabs">
        @foreach ($tabs as $t)
            <a class="tab {{ ($filters['status'] ?? null) === $t['key'] ? 'on' : '' }}"
               href="{{ route('flights.index', array_filter(array_merge($keep, ['status' => $t['key']]), fn ($v) => ! is_null($v) && $v !== '')) }}">{{ $t['label'] }}<span class="n">{{ $t['n'] }}</span></a>
        @endforeach
    </div>

    <form class="filters" method="GET" action="{{ route('flights.index') }}" id="filter-form">
        @if ($filters['status']) <input type="hidden" name="status" value="{{ $filters['status'] }}"> @endif
        <input class="input grow" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Filter by PNR, name, flight…">
        <select class="chip {{ $filters['airline'] ? 'set' : '' }}" name="airline" data-auto>
            <option value="">Airline</option>
            @foreach ($airlines as $a)
                <option value="{{ $a }}" {{ $filters['airline'] === $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <input class="chip {{ $filters['from'] ? 'set' : '' }}" type="date" name="from" value="{{ $filters['from'] }}" title="Departing on or after" data-auto>
        <select class="chip {{ $filters['payment'] ? 'set' : '' }}" name="payment" data-auto>
            <option value="">Payment</option>
            <option value="paid" {{ $filters['payment'] === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ $filters['payment'] === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
        <noscript><button class="btn btn-ghost btn-sm">Apply</button></noscript>
        <div class="result">
            @if ($active) <a href="{{ route('flights.index', array_filter(['status' => $filters['status']])) }}">Clear filters</a> @endif
            <span>{{ $bookings->total() }} {{ \Illuminate\Support\Str::plural('result', $bookings->total()) }}</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>PNR</th><th>Customer</th><th>Flight</th><th>Route</th><th>Departure</th>
                    <th class="right">Pax</th><th class="right">Total</th><th>Payment</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($bookings as $b)
                <tr class="row" data-href="{{ route('flights.show', $b) }}">
                    <td><a class="pnr" href="{{ route('flights.show', $b) }}">{{ $b->pnr }}</a></td>
                    <td><div class="strong">{{ $b->customer->name }}</div><div class="sub">{{ $b->customer->phone }}</div></td>
                    <td><div>{{ $b->airline }}</div><div class="sub mono">{{ $b->flight_no }}</div></td>
                    <td><div class="mono strong">{{ $b->origin }} → {{ $b->destination }}</div><div class="sub">{{ $b->duration }}</div></td>
                    <td><div>{{ $b->departure_at->format('d M Y') }}</div><div class="sub mono">{{ $b->departure_at->format('H:i') }}</div></td>
                    <td class="right mono">{{ $b->adults }}</td>
                    <td class="right mono strong nowrap"><span class="muted">{{ $b->currency }}</span> {{ number_format($b->total) }}</td>
                    <td><x-pay :value="optional($b->invoice)->payment_status" /></td>
                    <td><x-status :value="$b->status" /></td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">@if ($active || $filters['status']) No bookings match these filters. @else No bookings yet. <a href="{{ route('flights.create') }}">Create the first one</a>. @endif</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pager', ['p' => $bookings])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-auto]').forEach(function (el) {
        el.addEventListener('change', function () { document.getElementById('filter-form').submit(); });
    });
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
