@extends('layouts.app')
@section('title', 'Reports')
@section('content')
@php
    $money = function ($n) { return number_format($n); };
    $pct = function ($part, $whole) { return $whole > 0 ? round($part / $whole * 100) : 0; };
@endphp

<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"></span><b>Reports</b></div>
        <h1>Reports</h1>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-lg" href="{{ route('reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"><x-icon name="download" />Export CSV</a>
        <button type="button" class="btn btn-ghost btn-lg" onclick="window.print()"><x-icon name="print" />Print</button>
    </div>
</div>

<section class="panel">
    <form class="range" method="GET" action="{{ route('reports.index') }}">
        <div class="presets">
            @foreach ($presets as $label => [$a, $b])
                <a class="chip {{ $a->isSameDay($from) && $b->isSameDay($to) ? 'set' : '' }}" href="{{ route('reports.index', ['from' => $a->toDateString(), 'to' => $b->toDateString()]) }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="range-dates">
            <label>From <input class="input" type="date" name="from" value="{{ $from->toDateString() }}"></label>
            <label>To <input class="input" type="date" name="to" value="{{ $to->toDateString() }}"></label>
            <button class="btn btn-navy">Apply</button>
        </div>
    </form>
</section>

<div class="kpis">
    <div class="kpi">
        <div class="kpi-label">Revenue</div>
        <div class="kpi-val"><span class="unit">{{ $currency }}</span><b>{{ $money($summary['revenue']) }}</b></div>
        <div class="kpi-label">{{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('booking', $summary['count']) }} · avg {{ $money($summary['average']) }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Collected</div>
        <div class="kpi-val"><span class="unit">{{ $currency }}</span><b class="up">{{ $money($summary['paid']) }}</b></div>
        <div class="kpi-label">{{ $pct($summary['paid'], $summary['revenue']) }}% of revenue</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Outstanding</div>
        <div class="kpi-val"><span class="unit">{{ $currency }}</span><b class="{{ $summary['unpaid'] > 0 ? 'down' : '' }}">{{ $money($summary['unpaid']) }}</b></div>
        <div class="kpi-label">{{ $outstanding->count() }} unpaid {{ \Illuminate\Support\Str::plural('invoice', $outstanding->count()) }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Cancelled</div>
        <div class="kpi-val"><b>{{ $summary['cancelled'] }}</b></div>
        <div class="kpi-label">excluded from the figures</div>
    </div>
</div>

@if ($summary['otherCurrency'])
    <div class="alert alert-ok" style="background:#FDF1DC;border-color:#F3D9A8;color:#8A4B08">{{ $summary['otherCurrency'] }} {{ \Illuminate\Support\Str::plural('invoice', $summary['otherCurrency']) }} in another currency {{ $summary['otherCurrency'] == 1 ? 'is' : 'are' }} not included. Totals show {{ $currency }} only.</div>
@endif

<div class="report-grid">
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="chart-line" />Revenue by service</div>
        <div class="table-wrap">
            <table class="data fit">
                <thead><tr><th>Service</th><th class="right">Bookings</th><th class="right">Revenue</th><th class="right">Collected</th><th class="right">Outstanding</th></tr></thead>
                <tbody>
                    @foreach (['flight' => ['Flights', 'plane'], 'hotel' => ['Hotels', 'hotel']] as $key => [$label, $icon])
                        <tr>
                            <td><span class="svc"><x-icon :name="$icon" />{{ $label }}</span></td>
                            <td class="right">{{ $services[$key]['count'] }}</td>
                            <td class="right strong">{{ $money($services[$key]['revenue']) }}</td>
                            <td class="right up">{{ $money($services[$key]['paid']) }}</td>
                            <td class="right {{ $services[$key]['unpaid'] > 0 ? 'down' : '' }}">{{ $money($services[$key]['unpaid']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="chart-simple" />Last six months</div>
        <div class="panel-body">
            <div class="bars">
                @foreach ($months as $m)
                    <div class="bar-col" title="{{ $m['label'] }}: {{ $currency }} {{ $money($m['total']) }}">
                        <div class="bar-val">{{ $m['total'] > 0 ? \App\Support\FlightStats::compact($m['total']) : '' }}</div>
                        <div class="bar-stack" style="height: calc((100% - 44px) * {{ max(0.02, round($m['total'] / $maxMonth, 3)) }})">
                            @if ($m['total'] > 0)
                                <span class="seg seg-flight" style="flex: {{ $m['flight'] }}"></span>
                                <span class="seg seg-hotel" style="flex: {{ $m['hotel'] }}"></span>
                            @endif
                        </div>
                        <div class="bar-lbl">{{ $m['label'] }}</div>
                    </div>
                @endforeach
            </div>
            <div class="legend"><span><i class="seg-flight"></i>Flights</span><span><i class="seg-hotel"></i>Hotels</span></div>
        </div>
    </section>
</div>

<div class="report-grid three">
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="users" />Top customers</div>
        <table class="data narrow mini">
            <tbody>
            @forelse ($topCustomers as $c)
                <tr><td><a class="strong" href="{{ route('customers.show', $c['id']) }}">{{ $c['name'] }}</a><div class="sub">{{ $c['count'] }} {{ \Illuminate\Support\Str::plural('booking', $c['count']) }}</div></td><td class="right strong nowrap">{{ $money($c['total']) }}</td></tr>
            @empty
                <tr><td class="empty">No data in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="plane" />Top airlines</div>
        <table class="data narrow mini">
            <tbody>
            @forelse ($topAirlines as $a)
                <tr><td><span class="strong">{{ $a['name'] }}</span><div class="sub">{{ $a['count'] }} {{ \Illuminate\Support\Str::plural('ticket', $a['count']) }}</div></td><td class="right strong nowrap">{{ $money($a['total']) }}</td></tr>
            @empty
                <tr><td class="empty">No flights in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="hotel" />Top hotels</div>
        <table class="data narrow mini">
            <tbody>
            @forelse ($topHotels as $h)
                <tr><td><span class="strong">{{ $h['name'] }}</span><div class="sub">{{ $h['city'] }} · {{ $h['count'] }} {{ \Illuminate\Support\Str::plural('stay', $h['count']) }}</div></td><td class="right strong nowrap">{{ $money($h['total']) }}</td></tr>
            @empty
                <tr><td class="empty">No hotel stays in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="credit-card" />Outstanding payments</div>
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Invoice</th><th>Customer</th><th>Service</th><th>Issued</th><th class="right">Amount</th></tr></thead>
            <tbody>
            @forelse ($outstanding as $i)
                @php $isHotel = $i->invoiceable instanceof \App\Models\HotelBooking; @endphp
                <tr class="row" data-href="{{ route($isHotel ? 'hotels.show' : 'flights.show', $i->invoiceable_id) }}">
                    <td><a class="pnr" href="{{ route($isHotel ? 'hotels.show' : 'flights.show', $i->invoiceable_id) }}">{{ $i->invoice_no }}</a></td>
                    <td>{{ $i->customer->name }}</td>
                    <td>{{ $isHotel ? 'Hotel · ' . $i->invoiceable->booking_ref : 'Flight · ' . $i->invoiceable->pnr }}</td>
                    <td>{{ $i->issued_on->format('d M Y') }}</td>
                    <td class="right strong nowrap down">{{ $i->currency }} {{ $money($i->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Nothing outstanding in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
