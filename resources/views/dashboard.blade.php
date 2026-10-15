@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><b>Dashboard</b></div>
        <h1>Welcome back</h1>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-lg" href="{{ route('receipts.create') }}"><x-icon name="receipt" />Add receipt</a>
        <a class="btn btn-red btn-lg" href="{{ route('invoices.create') }}"><x-icon name="plus" />Add invoice</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi">
        <div class="kpi-label">Sales this month</div>
        <div class="kpi-val"><span class="unit">PKR</span><b>{{ \App\Support\FlightStats::compact($kpis['sales']) }}</b><small>{{ $kpis['invoices'] }} {{ \Illuminate\Support\Str::plural('invoice', $kpis['invoices']) }}</small></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">To receive from customers</div>
        <div class="kpi-val"><span class="unit">PKR</span><b class="{{ $kpis['receivable'] > 0 ? 'warn' : '' }}">{{ \App\Support\FlightStats::compact($kpis['receivable']) }}</b>@if ($kpis['overdue'])<small class="down">{{ $kpis['overdue'] }} overdue</small>@endif</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">To pay to suppliers</div>
        <div class="kpi-val"><span class="unit">PKR</span><b>{{ \App\Support\FlightStats::compact($kpis['payable']) }}</b></div>
    </div>
    @foreach ($accounts->take(1) as $a)
        <div class="kpi">
            <div class="kpi-label">{{ $a->name }} balance</div>
            <div class="kpi-val"><span class="unit">PKR</span><b class="{{ $a->balance < 0 ? 'down' : 'up' }}">{{ number_format($a->balance) }}</b></div>
        </div>
    @endforeach
</div>

@if ($accounts->count() > 1)
    <div class="stat-row" style="grid-template-columns:repeat({{ min(4, $accounts->count() - 1) }}, minmax(0,1fr))">
        @foreach ($accounts->slice(1)->take(4) as $a)
            <div class="kpi"><div class="kpi-label">{{ $a->name }}</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ number_format($a->balance) }}</b></div></div>
        @endforeach
    </div>
@endif

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="file-invoice" />Invoices waiting for payment</div>
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Invoice</th><th>Customer</th><th>Due date</th><th class="right">Invoice amount</th><th class="right">Balance</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($unpaid as $i)
                <tr class="row" data-href="{{ route('invoices.show', $i) }}">
                    <td class="nowrap"><a class="pnr" href="{{ route('invoices.show', $i) }}">{{ $i->invoice_no }}</a></td>
                    <td class="strong">{{ $i->customer->name }}</td>
                    <td class="nowrap {{ $i->due_date && $i->due_date->isPast() ? 'down' : '' }}">{{ optional($i->due_date)->format('d M Y') ?: '—' }}</td>
                    <td class="right">{{ number_format($i->total) }}</td>
                    <td class="right strong">{{ number_format($i->balance) }}</td>
                    <td><x-status :value="$i->payment_status" /></td>
                    <td class="right"><a class="btn btn-ghost btn-sm" href="{{ route('receipts.create', ['customer_id' => $i->customer_id, 'invoice_id' => $i->id]) }}">Receive</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Nothing is waiting for payment.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="plane-departure" />Departing in the next 14 days</div>
    @include('partials.booking-table', ['rows' => $upcoming, 'empty' => 'No departures in the next 14 days.'])
</section>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="hotel" />Hotel check-ins in the next 14 days</div>
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Reference</th><th>Guest</th><th>Hotel</th><th>Stay</th><th class="right">Total</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($stays as $b)
                <tr class="row" data-href="{{ route('hotels.show', $b) }}">
                    <td><a class="pnr" href="{{ route('hotels.show', $b) }}">{{ $b->booking_ref }}</a></td>
                    <td><div class="strong">{{ $b->guest }}</div><div class="sub">{{ $b->customer->phone }}</div></td>
                    <td><div class="strong">{{ $b->hotel_name }}</div><div class="sub">{{ $b->city }}</div></td>
                    <td><div>{{ $b->check_in->format('d M') }} → {{ $b->check_out->format('d M Y') }}</div><div class="sub">{{ $b->nights }} {{ \Illuminate\Support\Str::plural('night', $b->nights) }}</div></td>
                    <td class="right strong nowrap"><span class="muted">{{ $b->currency }}</span> {{ number_format($b->total) }}</td>
                    <td><x-pay :value="optional($b->invoice)->payment_status" /></td>
                    <td><x-status :value="$b->status" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No hotel check-ins in the next 14 days.</td></tr>
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
