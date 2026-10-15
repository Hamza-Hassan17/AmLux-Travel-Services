@extends('layouts.app')
@section('title', 'Sales report')
@section('content')
@php $money = function ($n) { return number_format($n); }; @endphp
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('reports.index') }}">Reports</a><span class="sep"></span><b>Sales</b></div>
        <h1>Sales report</h1>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-lg" href="{{ route('reports.sales.export', request()->query()) }}"><x-icon name="download" />Export CSV</a>
        <button type="button" class="btn btn-ghost btn-lg" onclick="window.print()"><x-icon name="print" />Print</button>
    </div>
</div>

<section class="panel">
    <form class="range" method="GET" action="{{ route('reports.sales') }}">
        <div class="presets">
            @foreach ($presets as $label => [$a, $b])
                <a class="chip {{ $a->isSameDay($from) && $b->isSameDay($to) ? 'set' : '' }}" href="{{ route('reports.sales', array_filter(['from' => $a->toDateString(), 'to' => $b->toDateString(), 'customer_id' => $customerId])) }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="range-dates">
            <label>From <input class="input" type="date" name="from" value="{{ $from->toDateString() }}"></label>
            <label>To <input class="input" type="date" name="to" value="{{ $to->toDateString() }}"></label>
            <select class="select" name="customer_id" style="width:200px">
                <option value="">All customers</option>
                @foreach ($customers as $c)<option value="{{ $c->id }}" {{ (string) $customerId === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <button class="btn btn-navy">Apply</button>
        </div>
    </form>
</section>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">Net sales (excl. tax)</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ $money($totals['net']) }}</b></div><div class="kpi-label">{{ $invoices->count() }} invoices</div></div>
    <div class="kpi"><div class="kpi-label">Tax</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ $money($totals['tax']) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Received</div><div class="kpi-val"><span class="unit">PKR</span><b class="up">{{ $money($totals['received']) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Balance due</div><div class="kpi-val"><span class="unit">PKR</span><b class="{{ $totals['balance'] > 0 ? 'down' : '' }}">{{ $money($totals['balance']) }}</b></div></div>
</div>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="chart-simple" />By service</div>
    <div class="table-wrap">
        <table class="data fit">
            <thead><tr><th>Service</th><th class="right">Lines</th><th class="right">Net sales</th><th class="right">Tax</th><th class="right">Total</th></tr></thead>
            <tbody>
            @foreach ($byService as $s)
                <tr><td class="strong">{{ $s['label'] }}</td><td class="right">{{ $s['count'] }}</td><td class="right">{{ $money($s['net']) }}</td><td class="right">{{ $money($s['tax']) }}</td><td class="right strong">{{ $money($s['total']) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="file-invoice" />Invoices</div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Services</th><th class="right">Net sales</th><th class="right">Tax</th><th class="right">Total</th><th class="right">Received</th><th class="right">Balance</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($invoices as $i)
                <tr class="row" data-href="{{ route('invoices.show', $i) }}">
                    <td class="nowrap">{{ $i->issued_on->format('d M Y') }}</td>
                    <td class="nowrap"><a class="pnr" href="{{ route('invoices.show', $i) }}">{{ $i->invoice_no }}</a></td>
                    <td class="strong">{{ $i->customer->name }}</td>
                    <td class="sub" style="color:var(--text)">{{ $i->lines->map(function ($l) { return ucfirst($l->service_type) . ' ' . $l->reference; })->implode(', ') }}</td>
                    <td class="right">{{ $money($i->total - $i->tax_total) }}</td>
                    <td class="right">{{ $money($i->tax_total) }}</td>
                    <td class="right strong">{{ $money($i->total) }}</td>
                    <td class="right amount-in">{{ $money($i->paid_amount) }}</td>
                    <td class="right {{ $i->balance > 0 ? 'amount-out' : '' }}">{{ $money($i->balance) }}</td>
                    <td><x-status :value="$i->payment_status" /></td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty">No invoices in this period.</td></tr>
            @endforelse
            </tbody>
            @if ($invoices->count())
                <tfoot>
                    <tr style="background:#F1F7FF;font-weight:700"><td colspan="4" class="right" style="padding:12px">Totals</td>
                        <td class="right" style="padding:12px">{{ $money($totals['net']) }}</td><td class="right" style="padding:12px">{{ $money($totals['tax']) }}</td>
                        <td class="right" style="padding:12px">{{ $money($totals['total']) }}</td><td class="right" style="padding:12px">{{ $money($totals['received']) }}</td>
                        <td class="right" style="padding:12px">{{ $money($totals['balance']) }}</td><td></td></tr>
                </tfoot>
            @endif
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
