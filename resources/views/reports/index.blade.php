@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"></span><b>Reports</b></div>
        <h1>Reports</h1>
    </div>
</div>

<div class="report-tiles">
    <a class="report-tile" href="{{ route('reports.sales') }}">
        <span class="big"><x-icon name="chart-line" /></span>
        <div>
            <h2>Sales report</h2>
            <div class="sub">Every invoice in a period with net sales, tax, received and balance, grouped by service.</div>
            <div style="margin-top:8px"><b>PKR {{ number_format($salesTotal) }}</b> <span class="sub">invoiced in {{ $from->format('F') }} · {{ $salesCount }} {{ \Illuminate\Support\Str::plural('invoice', $salesCount) }}</span></div>
        </div>
    </a>
    <a class="report-tile" href="{{ route('reports.pnl') }}">
        <span class="big"><x-icon name="scale-balanced" /></span>
        <div>
            <h2>Profit &amp; loss</h2>
            <div class="sub">Sales less buying cost, refunds and agent commissions for any period.</div>
            <div style="margin-top:8px"><b class="{{ $netProfit < 0 ? 'down' : 'up' }}">PKR {{ number_format($netProfit) }}</b> <span class="sub">net profit in {{ $from->format('F') }}</span></div>
        </div>
    </a>
</div>
@endsection
