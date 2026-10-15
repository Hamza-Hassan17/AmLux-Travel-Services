@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="crumbs"><a href="{{ route('customers.index') }}">Directory</a><span class="sep"></span><a href="{{ route('customers.index') }}">Customers</a><span class="sep"></span><b>{{ $customer->name }}</b></div>

<div class="detail-head">
    <div class="person">
        <div class="avatar">{{ $customer->initials }}</div>
        <div>
            <h1>{{ $customer->name }}</h1>
            <div class="sub">{{ $customer->type === 'walk_in' ? 'Walk-in' : ($customer->nationality ?: 'Customer') }} · {{ $customer->invoices->count() }} {{ \Illuminate\Support\Str::plural('invoice', $customer->invoices->count()) }}</div>
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost" href="{{ route('receipts.create', ['customer_id' => $customer->id]) }}"><x-icon name="receipt" />Receive payment</a>
        <a class="btn btn-red" href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}"><x-icon name="plus" />New invoice</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">Invoiced</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ number_format($customer->invoices->sum('total')) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Received</div><div class="kpi-val"><span class="unit">PKR</span><b class="up">{{ number_format($customer->receipts->sum('amount')) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Outstanding</div><div class="kpi-val"><span class="unit">PKR</span><b class="{{ $customer->outstanding > 0 ? 'down' : '' }}">{{ number_format($customer->outstanding) }}</b></div></div>
    <div class="kpi"><div class="kpi-label">Advance held</div><div class="kpi-val"><span class="unit">PKR</span><b>{{ number_format($customer->advance) }}</b></div></div>
</div>

<div class="detail-grid">
    <div class="stack">
        <section class="panel panel-clip">
            <div class="panel-head"><x-icon name="file-invoice" />Invoices</div>
            <div class="table-wrap">
                <table class="data narrow">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Services</th><th class="right">Amount</th><th class="right">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($customer->invoices->sortByDesc('issued_on') as $i)
                        <tr class="row" data-href="{{ route('invoices.show', $i) }}">
                            <td><a class="pnr" href="{{ route('invoices.show', $i) }}">{{ $i->invoice_no }}</a></td>
                            <td class="nowrap">{{ $i->issued_on->format('d M Y') }}</td>
                            <td class="sub" style="color:var(--text)">{{ $i->lines->map(function ($l) { return ucfirst($l->service_type) . ' ' . $l->reference; })->implode(', ') }}</td>
                            <td class="right strong nowrap">{{ number_format($i->total) }}</td>
                            <td class="right nowrap {{ $i->balance > 0 ? 'amount-out' : '' }}">{{ number_format($i->balance) }}</td>
                            <td><x-status :value="$i->payment_status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No invoices yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel panel-clip">
            <div class="panel-head"><x-icon name="receipt" />Receipts</div>
            <div class="table-wrap">
                <table class="data narrow">
                    <thead><tr><th>Receipt</th><th>Date</th><th>Mode</th><th class="right">Amount</th><th class="right">Advance</th></tr></thead>
                    <tbody>
                    @forelse ($customer->receipts->sortByDesc('received_on') as $r)
                        <tr class="row" data-href="{{ route('receipts.show', $r) }}">
                            <td><a class="pnr" href="{{ route('receipts.show', $r) }}">{{ $r->receipt_no }}</a></td>
                            <td>{{ $r->received_on->format('d M Y') }}</td>
                            <td>{{ optional($r->account)->name }}</td>
                            <td class="right strong amount-in">{{ number_format($r->amount) }}</td>
                            <td class="right">{{ $r->unallocated > 0 ? number_format($r->unallocated) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No receipts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="panel">
        <div class="panel-head"><x-icon name="id-card" />Contact &amp; details</div>
        <div class="panel-body">
            <dl class="dl" style="margin:0">
                <dt>Phone</dt><dd>{{ $customer->phone ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $customer->email ?: '—' }}</dd>
                <dt>Passport</dt><dd>{{ $customer->passport_no ?: '—' }}</dd>
                <dt>Nationality</dt><dd>{{ $customer->nationality ?: '—' }}</dd>
                <dt>Country</dt><dd>{{ $customer->country ?: '—' }}</dd>
                <dt>Date of birth</dt><dd>{{ optional($customer->date_of_birth)->format('d M Y') ?: '—' }}</dd>
                <dt>Tax no.</dt><dd>{{ $customer->tax_no ?: '—' }}</dd>
                <dt>Opening balance</dt><dd>PKR {{ number_format($customer->opening_balance) }}@if ($customer->opening_balance > 0) <span class="sub">(PKR {{ number_format($customer->opening_due) }} unpaid)</span>@endif</dd>
                <dt>Address</dt><dd>{{ $customer->address ?: '—' }}</dd>
            </dl>
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
