@extends('layouts.app')
@section('title', 'Add refund')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('refunds.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('refunds.index') }}">Refunds</a><span class="sep"></span><b>New</b></div>
        <h1>Add refund</h1>
    </div>
</div>

<section class="panel">
    <form class="pick-bar" method="GET" action="{{ route('refunds.create') }}">
        <div class="field">
            <label for="invoice_pick">Invoice <span class="req">*</span></label>
            <select id="invoice_pick" name="invoice_id" class="select" onchange="this.form.submit()">
                <option value="">Select invoice</option>
                @foreach ($invoices as $i)
                    <option value="{{ $i->id }}" {{ $invoice && $invoice->id === $i->id ? 'selected' : '' }}>{{ $i->invoice_no }} · {{ $i->customer->name }} · PKR {{ number_format($i->total) }}</option>
                @endforeach
            </select>
        </div>
    </form>
</section>

@if ($invoice)
<form method="POST" action="{{ route('refunds.store') }}" id="refund-form">
    @csrf
    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
    <div class="form-layout">
        <div class="stack">
            <section class="panel panel-clip">
                <div class="panel-head"><x-icon name="suitcase" />Which service is being refunded?</div>
                <div class="table-wrap">
                    <table class="data narrow">
                        <thead><tr><th></th><th>Service</th><th>Details</th><th class="right">Buying</th><th class="right">Charged</th><th class="right">Already refunded</th></tr></thead>
                        <tbody>
                        @foreach ($invoice->lines as $l)
                            <tr>
                                <td><input type="radio" name="invoice_line_id" value="{{ $l->id }}" id="l{{ $l->id }}" {{ (string) old('invoice_line_id', $line_id ?: ($invoice->lines->count() === 1 ? $l->id : '')) === (string) $l->id ? 'checked' : '' }} required></td>
                                <td><label for="l{{ $l->id }}" class="svc"><x-icon :name="$l->service_type === 'flight' ? 'plane' : 'hotel'" />{{ ucfirst($l->service_type) }}</label></td>
                                <td>{{ $l->reference }} · {{ $l->passenger }}</td>
                                <td class="right">{{ number_format($l->buying_price) }}</td>
                                <td class="right strong">{{ number_format($l->line_total) }}</td>
                                <td class="right">{{ number_format($l->refunds->sum('customer_refund')) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="rotate-left" />Refund amounts</div>
                <div class="panel-body grid">
                    <div class="field c4"><label for="refunded_on">Refund date <span class="req">*</span></label><input id="refunded_on" type="date" name="refunded_on" class="input" value="{{ old('refunded_on', now()->toDateString()) }}" required></div>
                    <div class="field c4"><label for="supplier_refund">Refund from supplier <span class="req">*</span></label><input id="supplier_refund" type="number" step="0.01" min="0" name="supplier_refund" class="input" value="{{ old('supplier_refund') }}" required></div>
                    <div class="field c4"><label for="margin">Our refund charge</label><input id="margin" type="number" step="0.01" min="0" name="margin" class="input" value="{{ old('margin', 0) }}"></div>
                    <div class="field c12"><label for="remarks">Remarks</label><input id="remarks" name="remarks" class="input" value="{{ old('remarks') }}"></div>
                </div>
            </section>
        </div>

        <aside class="panel summary">
            <div class="panel-head"><x-icon name="coins" />Summary</div>
            <div class="panel-body">
                <div class="totals-box">
                    <div class="row"><span>From supplier</span><b id="r-sup">0</b></div>
                    <div class="row"><span>Our charge</span><b id="r-margin">0</b></div>
                    <div class="row grand"><span>Refund to customer</span><b id="r-cust">0</b></div>
                </div>
                <p class="sub" style="margin-top:12px">If the invoice is still unpaid, the refund first reduces what the customer owes. Any cash owed back appears under Payments.</p>
                <div class="sum-actions">
                    <button class="btn btn-red btn-lg">Save refund</button>
                    <a class="btn btn-ghost" href="{{ route('refunds.index') }}">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@else
    <div class="panel"><div class="panel-body empty" style="color:var(--muted)">Choose the invoice you are refunding.</div></div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var f = document.getElementById('refund-form'); if (!f) return;
    function upd() {
        var s = parseFloat(f.elements.supplier_refund.value) || 0, m = parseFloat(f.elements.margin.value) || 0;
        document.getElementById('r-sup').textContent = s.toLocaleString('en-US');
        document.getElementById('r-margin').textContent = m.toLocaleString('en-US');
        document.getElementById('r-cust').textContent = Math.max(0, s - m).toLocaleString('en-US');
    }
    f.addEventListener('input', upd); upd();
})();
</script>
@endpush
