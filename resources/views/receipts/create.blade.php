@extends('layouts.app')
@section('title', 'Add receipt')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('receipts.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('receipts.index') }}">Receipts</a><span class="sep"></span><b>New</b></div>
        <h1>Add receipt</h1>
    </div>
</div>

<section class="panel">
    <form class="pick-bar" method="GET" action="{{ route('receipts.create') }}">
        <div class="field">
            <label for="customer_pick">Receive from <span class="req">*</span></label>
            <select id="customer_pick" name="customer_id" class="select" onchange="this.form.submit()">
                <option value="">Select customer</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}" {{ $customer && $customer->id === $c->id ? 'selected' : '' }}>{{ $c->name }}{{ $c->type === 'walk_in' ? ' (walk-in)' : '' }}{{ $c->phone ? ' · ' . $c->phone : '' }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button class="btn btn-navy">Load</button></noscript>
        @if ($customer)
            <div class="balance-chip" style="background:#F1F7FF;box-shadow:none">Outstanding <b>PKR {{ number_format($customer->outstanding) }}</b>@if ($customer->advance > 0) · Advance <b>PKR {{ number_format($customer->advance) }}</b>@endif</div>
        @endif
    </form>
</section>

@if ($customer)
<form method="POST" action="{{ route('receipts.store') }}" id="receipt-form">
    @csrf
    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
    <input type="hidden" name="auto" value="1">
    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><x-icon name="receipt" />Receipt details</div>
                <div class="panel-body grid">
                    <div class="field c4"><label for="amount">Amount <span class="req">*</span></label><input id="amount" type="number" step="0.01" min="0.01" name="amount" class="input" value="{{ old('amount', $invoice_id ? optional($open['invoices']->firstWhere('id', (int) $invoice_id))->balance : '') }}" required></div>
                    <div class="field c4"><label for="received_on">Receipt date <span class="req">*</span></label><input id="received_on" type="date" name="received_on" class="input" value="{{ old('received_on', now()->toDateString()) }}" required></div>
                    <div class="field c4">
                        <label for="account_id">Mode of payment <span class="req">*</span></label>
                        <select id="account_id" name="account_id" class="select" required>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" {{ (string) old('account_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c6"><label for="reference">Reference (cheque / transaction no.)</label><input id="reference" name="reference" class="input" value="{{ old('reference') }}"></div>
                    <div class="field c6"><label for="remarks">Remarks</label><input id="remarks" name="remarks" class="input" value="{{ old('remarks') }}"></div>
                </div>
            </section>

            <section class="panel panel-clip">
                <div class="panel-head"><x-icon name="file-invoice" />Settle against</div>
                @include('partials.alloc-invoices', ['open' => $open, 'preselect' => $invoice_id])
            </section>
        </div>

        <aside class="panel summary">
            <div class="panel-head"><x-icon name="coins" />Summary</div>
            <div class="panel-body">
                <div class="totals-box">
                    <div class="row"><span>Receipt amount</span><b id="s-amount">0</b></div>
                    <div class="row"><span>Applied to invoices</span><b id="s-applied">0</b></div>
                    <div class="row grand"><span>Kept as advance</span><b id="s-advance">0</b></div>
                </div>
                <p class="sub" style="margin-top:12px">Leave all amounts empty to apply the receipt to the oldest invoices first. Anything left over is kept as an advance you can match later.</p>
                <div class="sum-actions">
                    <button class="btn btn-red btn-lg">Save receipt</button>
                    <a class="btn btn-ghost" href="{{ route('receipts.index') }}">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@else
    <div class="panel"><div class="panel-body empty" style="color:var(--muted)">Choose a customer to see their open invoices.</div></div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('receipt-form');
    if (!form) return;
    var amount = form.elements.amount;
    function inputs() { return form.querySelectorAll('.alloc-input'); }
    function fmt(n) { return n.toLocaleString('en-US', { maximumFractionDigits: 2 }); }
    function applied() { var t = 0; inputs().forEach(function (i) { t += parseFloat(i.value) || 0; }); return t; }
    function update() {
        var a = parseFloat(amount.value) || 0, p = applied();
        document.getElementById('s-amount').textContent = fmt(a);
        document.getElementById('s-applied').textContent = fmt(p);
        document.getElementById('s-advance').textContent = fmt(Math.max(0, a - p));
    }
    form.addEventListener('input', update);
    form.addEventListener('click', function (e) {
        var b = e.target.closest('[data-select]');
        if (!b) return;
        var inp = form.querySelector(b.dataset.select);
        var a = parseFloat(amount.value) || 0, others = applied() - (parseFloat(inp.value) || 0);
        var room = a > 0 ? Math.max(0, a - others) : parseFloat(b.dataset.max);
        inp.value = Math.min(room, parseFloat(b.dataset.max)) || '';
        if (!a) { amount.value = (applied()).toFixed(2); }
        update();
    });
    update();
})();
</script>
@endpush
