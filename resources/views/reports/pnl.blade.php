@extends('layouts.app')
@section('title', 'Profit & loss')
@section('content')
@php $m = function ($n) { return number_format($n); }; @endphp
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('reports.index') }}">Reports</a><span class="sep"></span><b>Profit &amp; loss</b></div>
        <h1>Profit &amp; loss</h1>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-lg" href="{{ route('reports.pnl.export', request()->query()) }}"><x-icon name="download" />Export CSV</a>
        <button type="button" class="btn btn-ghost btn-lg" onclick="window.print()"><x-icon name="print" />Print</button>
    </div>
</div>

<section class="panel">
    <form class="range" method="GET" action="{{ route('reports.pnl') }}">
        <div class="presets">
            @foreach ($presets as $label => [$a, $b])
                <a class="chip {{ $a->isSameDay($from) && $b->isSameDay($to) ? 'set' : '' }}" href="{{ route('reports.pnl', ['from' => $a->toDateString(), 'to' => $b->toDateString()]) }}">{{ $label }}</a>
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
    <div class="kpi"><div class="kpi-label">Net sales</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ $m($p['netSales']) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Gross profit</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ $m($p['grossProfit']) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Net profit</div><div class="kpi-val"><span class="unit">PKR</span><b class="{{ $p['netProfit'] < 0 ? 'down' : 'up' }}">{{ $m($p['netProfit']) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Margin</div><div class="kpi-val"><b>{{ $p['margin'] }}%</b></div></div>
</div>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="scale-balanced" />{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }} · {{ $p['invoiceCount'] }} {{ \Illuminate\Support\Str::plural('invoice', $p['invoiceCount']) }}</div>
    <table class="pl">
        <tr class="head"><td>Income</td><td>PKR</td></tr>
        <tr class="sub"><td>Flight sales</td><td>{{ $m($p['sales']['flight']) }}</td></tr>
        <tr class="sub"><td>Hotel sales</td><td>{{ $m($p['sales']['hotel']) }}</td></tr>
        <tr class="sum"><td>Total sales (excluding tax)</td><td>{{ $m($p['salesTotal']) }}</td></tr>
        <tr class="sub"><td>Less: refunds to customers</td><td class="neg">− {{ $m($p['customerRefunds']) }}</td></tr>
        <tr class="sum"><td>Net sales</td><td>{{ $m($p['netSales']) }}</td></tr>

        <tr class="head"><td>Cost of sales</td><td>PKR</td></tr>
        <tr class="sub"><td>Flight tickets bought</td><td>{{ $m($p['cost']['flight']) }}</td></tr>
        <tr class="sub"><td>Hotels bought</td><td>{{ $m($p['cost']['hotel']) }}</td></tr>
        <tr class="sum"><td>Total cost of sales</td><td>{{ $m($p['costTotal']) }}</td></tr>
        <tr class="sub"><td>Less: refunds received from suppliers</td><td class="neg">− {{ $m($p['supplierRefunds']) }}</td></tr>
        <tr class="sum"><td>Net cost of sales</td><td>{{ $m($p['netCost']) }}</td></tr>

        <tr class="grand"><td>Gross profit</td><td>{{ $m($p['grossProfit']) }}</td></tr>
        <tr class="sub"><td>Less: commission agents</td><td class="neg">− {{ $m($p['commissions']) }}</td></tr>
        <tr class="grand"><td>Net profit</td><td class="{{ $p['netProfit'] < 0 ? 'neg' : '' }}">{{ $m($p['netProfit']) }}</td></tr>
    </table>
    <div class="tbl-note">Tax collected in the period (PKR {{ $m($p['tax']) }}) is not counted as income. Operating expenses are not recorded in this system yet, so net profit is before overheads.</div>
</section>
@endsection
