@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
@php
    $tabs = [
        [null, 'All', $counts->sum()],
        ['unpaid', 'Unpaid', $counts->get('unpaid', 0)],
        ['partial', 'Partial', $counts->get('partial', 0)],
        ['paid', 'Paid', $counts->get('paid', 0)],
        ['cancelled', 'Cancelled', $counts->get('cancelled', 0)],
    ];
    $keep = request()->except('page');
    $active = collect($filters)->only(['from', 'to', 'q'])->filter()->isNotEmpty();
@endphp

<div class="page-head">
    <div>
        <div class="crumbs">Transactions<span class="sep"></span><b>Invoices</b></div>
        <h1>Invoices</h1>
    </div>
    <div class="actions">
        <a class="btn btn-red btn-lg" href="{{ route('invoices.create') }}"><x-icon name="plus" />Add invoice</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">Invoices shown</div><div class="kpi-val"><b>{{ $invoices->total() }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Invoiced total</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ number_format($sum->total) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Outstanding balance</div><div class="kpi-val"><span class="unit">PKR</span><b class="{{ $sum->balance > 0 ? 'down' : '' }}">{{ number_format($sum->balance) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Collected</div><div class="kpi-val"><span class="unit">PKR</span><b class="up">{{ number_format($sum->total - $sum->balance) }}</b></div></div>
</div>

<section class="panel panel-clip">
    <div class="tabs">
        @foreach ($tabs as [$key, $label, $n])
            <a class="tab {{ ($filters['status'] ?? null) === $key ? 'on' : '' }}"
               href="{{ route('invoices.index', array_filter(array_merge($keep, ['status' => $key]), fn ($v) => ! is_null($v) && $v !== '')) }}">{{ $label }}<span class="n">{{ $n }}</span></a>
        @endforeach
    </div>

    <form class="filters" method="GET" action="{{ route('invoices.index') }}">
        @if ($filters['status']) <input type="hidden" name="status" value="{{ $filters['status'] }}"> @endif
        <input class="input" style="width:160px" type="date" name="from" value="{{ $filters['from'] }}" title="From date">
        <input class="input" style="width:160px" type="date" name="to" value="{{ $filters['to'] }}" title="To date">
        <input class="input grow" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Invoice no, name, mobile, PNR…">
        <button class="btn btn-navy btn-sm">Search</button>
        <div class="result">
            @if ($active) <a href="{{ route('invoices.index', array_filter(['status' => $filters['status']])) }}">Clear filters</a> @endif
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Invoice no</th><th>Date</th><th>Bill to</th><th>Services</th><th class="right">Invoice amount</th><th class="right">Balance</th><th>Status</th><th>Due date</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($invoices as $i)
                <tr class="row" data-href="{{ route('invoices.show', $i) }}">
                    <td class="nowrap"><a class="pnr" href="{{ route('invoices.show', $i) }}">{{ $i->invoice_no }}</a></td>
                    <td class="nowrap">{{ $i->issued_on->format('d M Y') }}</td>
                    <td><div class="strong">{{ $i->customer->name }}</div><div class="sub">{{ $i->bill_to_type === 'walk_in' ? 'Walk-in' : $i->customer->phone }}</div></td>
                    <td>
                        @foreach ($i->lines->take(2) as $l)
                            <div class="sub" style="color:var(--text)">{{ ucfirst($l->service_type) }} · {{ $l->reference }}</div>
                        @endforeach
                        @if ($i->lines->count() > 2)<div class="sub">+{{ $i->lines->count() - 2 }} more</div>@endif
                    </td>
                    <td class="right strong nowrap">{{ number_format($i->total) }}</td>
                    <td class="right nowrap {{ $i->balance > 0 ? 'down' : '' }}">{{ number_format($i->balance) }}</td>
                    <td><x-status :value="$i->payment_status" /></td>
                    <td class="nowrap {{ $i->due_date && $i->balance > 0 && $i->due_date->isPast() ? 'down' : '' }}">{{ optional($i->due_date)->format('d M Y') }}</td>
                    <td class="right nowrap">
                        <a class="icon-btn" href="{{ route('invoices.edit', $i) }}" title="Edit"><x-icon name="pen" /></a>
                        <a class="icon-btn" href="{{ route('invoices.pdf', $i) }}" title="Download PDF"><x-icon name="print" /></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No invoices found. <a href="{{ route('invoices.create') }}">Create the first invoice</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $invoices])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
