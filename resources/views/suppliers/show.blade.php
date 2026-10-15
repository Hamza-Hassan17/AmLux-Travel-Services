@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<div class="crumbs"><a href="{{ route('suppliers.index') }}">Directory</a><span class="sep"></span><a href="{{ route('suppliers.index') }}">Suppliers</a><span class="sep"></span><b>{{ $supplier->name }}</b></div>

<div class="detail-head">
    <div class="person">
        <div class="avatar" style="background:var(--navy)"><x-icon name="building" /></div>
        <div><h1>{{ $supplier->name }}</h1><div class="sub">{{ $supplier->phone ?: 'No phone' }}{{ $supplier->email ? ' · ' . $supplier->email : '' }}</div></div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost" href="{{ route('suppliers.edit', $supplier) }}"><x-icon name="pen" />Edit</a>
        <a class="btn btn-red" href="{{ route('payments.create', ['payee_type' => 'supplier', 'supplier_id' => $supplier->id]) }}"><x-icon name="wallet" />Pay supplier</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">We owe</div><div class="kpi-val"><span class="unit">PKR</span><b class="{{ $supplier->payable > 0 ? 'down' : '' }}">{{ number_format($supplier->payable) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Opening balance</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ number_format($supplier->opening_balance) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Purchases</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ number_format($lines->sum('buying_price')) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Paid so far</div><div class="kpi-val"><span class="unit">PKR</span><b class="up">{{ number_format($payments->sum('amount')) }}</b></div></div>
</div>

<div class="report-grid">
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="file-invoice" />Purchases</div>
        <div class="table-wrap">
            <table class="data fit">
                <thead><tr><th>Invoice</th><th>Service</th><th class="right">Buying</th><th class="right">Payable</th></tr></thead>
                <tbody>
                @forelse ($lines as $l)
                    <tr>
                        <td><a class="pnr" href="{{ route('invoices.show', $l->invoice_id) }}">{{ optional($l->invoice)->invoice_no }}</a></td>
                        <td>{{ ucfirst($l->service_type) }} · {{ $l->reference }}</td>
                        <td class="right">{{ number_format($l->buying_price) }}</td>
                        <td class="right strong {{ $l->supplier_payable > 0 ? 'amount-out' : '' }}">{{ number_format($l->supplier_payable) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No purchases from this supplier yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="wallet" />Payments made</div>
        <div class="table-wrap">
            <table class="data fit">
                <thead><tr><th>Payment</th><th>Date</th><th>Mode</th><th class="right">Amount</th></tr></thead>
                <tbody>
                @forelse ($payments as $p)
                    <tr class="row" data-href="{{ route('payments.show', $p) }}">
                        <td><a class="pnr" href="{{ route('payments.show', $p) }}">{{ $p->payment_no }}</a></td>
                        <td>{{ $p->paid_on->format('d M Y') }}</td>
                        <td>{{ optional($p->account)->name }}</td>
                        <td class="right strong">{{ number_format($p->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No payments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
