@extends('layouts.app')
@section('title', 'Add payment')
@section('content')
@php $isSupplier = $type === 'supplier'; $party = $isSupplier ? $supplier : $customer; @endphp
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('payments.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('payments.index') }}">Payments</a><span class="sep"></span><b>New</b></div>
        <h1>Add payment</h1>
    </div>
</div>

<section class="panel">
    <form class="pick-bar" method="GET" action="{{ route('payments.create') }}">
        <div class="field" style="max-width:240px">
            <label for="payee_type">Pay to</label>
            <select id="payee_type" name="payee_type" class="select" onchange="this.form.submit()">
                <option value="supplier" {{ $isSupplier ? 'selected' : '' }}>Supplier</option>
                <option value="customer" {{ ! $isSupplier ? 'selected' : '' }}>Customer (refund)</option>
            </select>
        </div>
        @if ($isSupplier)
            <div class="field">
                <label for="supplier_id">Supplier <span class="req">*</span></label>
                <select id="supplier_id" name="supplier_id" class="select" onchange="this.form.submit()">
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}" {{ $supplier && $supplier->id === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            @if ($supplier)<div class="balance-chip" style="background:#F1F7FF;box-shadow:none">We owe <b>PKR {{ number_format($supplier->payable) }}</b></div>@endif
        @else
            <div class="field">
                <label for="customer_id">Customer <span class="req">*</span></label>
                <select id="customer_id" name="customer_id" class="select" onchange="this.form.submit()">
                    <option value="">Select customer</option>
                    @foreach ($customers as $c)<option value="{{ $c->id }}" {{ $customer && $customer->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
            </div>
        @endif
    </form>
</section>

@if ($party)
<form method="POST" action="{{ route('payments.store') }}" id="payment-form">
    @csrf
    <input type="hidden" name="payee_type" value="{{ $type }}">
    <input type="hidden" name="{{ $isSupplier ? 'supplier_id' : 'customer_id' }}" value="{{ $party->id }}">
    <input type="hidden" name="auto" value="1">
    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><x-icon name="wallet" />Payment details</div>
                <div class="panel-body grid">
                    <div class="field c4"><label for="amount">Amount <span class="req">*</span></label><input id="amount" type="number" step="0.01" min="0.01" name="amount" class="input" value="{{ old('amount') }}" required></div>
                    <div class="field c4"><label for="paid_on">Payment date <span class="req">*</span></label><input id="paid_on" type="date" name="paid_on" class="input" value="{{ old('paid_on', now()->toDateString()) }}" required></div>
                    <div class="field c4">
                        <label for="account_id">Paid from <span class="req">*</span></label>
                        <select id="account_id" name="account_id" class="select" required>
                            @foreach ($accounts as $a)<option value="{{ $a->id }}" {{ (string) old('account_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }} · {{ number_format($a->balance) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field c6"><label for="reference">Reference (cheque / transaction no.)</label><input id="reference" name="reference" class="input" value="{{ old('reference') }}"></div>
                    <div class="field c6"><label for="remarks">Remarks</label><input id="remarks" name="remarks" class="input" value="{{ old('remarks') }}"></div>
                </div>
            </section>

            <section class="panel panel-clip">
                <div class="panel-head"><x-icon name="file-invoice" />{{ $isSupplier ? 'Pay against purchases' : 'Pay against refunds' }}</div>
                <div class="table-wrap">
                    <table class="data narrow">
                        <thead><tr><th>#</th><th>Document</th><th>Details</th><th class="right">Payable</th><th class="right">Pay</th><th></th></tr></thead>
                        <tbody>
                        @php $n = 0; @endphp
                        @if ($isSupplier && $open['opening'] > 0)
                            <tr><td>OB</td><td class="strong">Opening balance</td><td>—</td><td class="right strong">{{ number_format($open['opening']) }}</td>
                                <td class="right"><input class="input alloc-input" type="number" step="0.01" min="0" max="{{ $open['opening'] }}" id="alloc-opening" name="alloc_opening" value="{{ old('alloc_opening') }}"></td>
                                <td class="right"><button type="button" class="btn btn-ghost btn-sm" data-select="#alloc-opening" data-max="{{ $open['opening'] }}">Select</button></td></tr>
                        @endif
                        @foreach ($isSupplier ? $open['lines'] : $open['refunds'] as $item)
                            @php $n++; $due = $isSupplier ? $item->supplier_payable : $item->outstanding; @endphp
                            <tr>
                                <td>{{ $n }}</td>
                                <td class="nowrap"><a class="pnr" href="{{ route('invoices.show', $item->invoice) }}">{{ $isSupplier ? $item->invoice->invoice_no : $item->refund_no }}</a></td>
                                <td>@if ($isSupplier){{ ucfirst($item->service_type) }} · {{ $item->reference }} · {{ $item->passenger }}@else Refund of {{ $item->invoice->invoice_no }}@endif</td>
                                <td class="right strong nowrap">{{ number_format($due) }}</td>
                                <td class="right"><input class="input alloc-input" type="number" step="0.01" min="0" max="{{ $due }}" id="alloc-{{ $item->id }}" name="alloc[{{ $item->id }}]" value="{{ old('alloc.' . $item->id) }}"></td>
                                <td class="right"><button type="button" class="btn btn-ghost btn-sm" data-select="#alloc-{{ $item->id }}" data-max="{{ $due }}">Select</button></td>
                            </tr>
                        @endforeach
                        @if ($n === 0 && ! ($isSupplier && $open['opening'] > 0))
                            <tr><td colspan="6" class="empty">Nothing is payable to {{ $party->name }} right now. The payment will be recorded as an advance.</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="panel summary">
            <div class="panel-head"><x-icon name="coins" />Summary</div>
            <div class="panel-body">
                <div class="totals-box">
                    <div class="row"><span>Payment amount</span><b id="s-amount">0</b></div>
                    <div class="row"><span>Applied</span><b id="s-applied">0</b></div>
                    <div class="row grand"><span>Advance</span><b id="s-advance">0</b></div>
                </div>
                <p class="sub" style="margin-top:12px">Leave all amounts empty to pay the oldest items first.</p>
                <div class="sum-actions">
                    <button class="btn btn-red btn-lg">Save payment</button>
                    <a class="btn btn-ghost" href="{{ route('payments.index') }}">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@else
    <div class="panel"><div class="panel-body empty" style="color:var(--muted)">Choose who you are paying to see what is outstanding.</div></div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('payment-form');
    if (!form) return;
    var amount = form.elements.amount;
    function applied() { var t = 0; form.querySelectorAll('.alloc-input').forEach(function (i) { t += parseFloat(i.value) || 0; }); return t; }
    function fmt(n) { return n.toLocaleString('en-US', { maximumFractionDigits: 2 }); }
    function update() {
        var a = parseFloat(amount.value) || 0, p = applied();
        document.getElementById('s-amount').textContent = fmt(a);
        document.getElementById('s-applied').textContent = fmt(p);
        document.getElementById('s-advance').textContent = fmt(Math.max(0, a - p));
    }
    form.addEventListener('input', update);
    form.addEventListener('click', function (e) {
        var b = e.target.closest('[data-select]'); if (!b) return;
        var inp = form.querySelector(b.dataset.select);
        var a = parseFloat(amount.value) || 0, others = applied() - (parseFloat(inp.value) || 0);
        var room = a > 0 ? Math.max(0, a - others) : parseFloat(b.dataset.max);
        inp.value = Math.min(room, parseFloat(b.dataset.max)) || '';
        if (!a) { amount.value = applied().toFixed(2); }
        update();
    });
    update();
})();
</script>
@endpush
