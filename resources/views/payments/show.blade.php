@extends('layouts.app')
@section('title', 'Payment ' . $payment->payment_no)
@section('content')
<div class="crumbs"><a href="{{ route('payments.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('payments.index') }}">Payments</a><span class="sep"></span><b>{{ $payment->payment_no }}</b></div>

<div class="detail-head">
    <div class="ref">
        <div class="sub">Payment voucher</div>
        <div class="ref-line"><span class="ref-pnr" style="font-size:32px">{{ $payment->payment_no }}</span>
            @if ($payment->unallocated > 0)<span class="pill pill-partial">Advance PKR {{ number_format($payment->unallocated) }}</span>@endif</div>
    </div>
    <div class="actions">
        <a class="btn btn-navy" href="{{ route('payments.pdf', $payment) }}"><x-icon name="download" />Download voucher</a>
        <button class="btn btn-ghost" onclick="window.print()"><x-icon name="print" />Print</button>
    </div>
</div>

<div class="detail-grid">
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="file-invoice" />Applied to</div>
        <div class="table-wrap">
            <table class="data narrow">
                <thead><tr><th>Document</th><th>Details</th><th class="right">Amount</th></tr></thead>
                <tbody>
                @forelse ($payment->allocations as $a)
                    <tr>
                        @if ($a->line)
                            <td><a class="pnr" href="{{ route('invoices.show', $a->line->invoice_id) }}">{{ optional($a->line->invoice)->invoice_no }}</a></td>
                            <td>{{ ucfirst($a->line->service_type) }} · {{ $a->line->reference }} · {{ $a->line->passenger }}</td>
                        @elseif ($a->refund)
                            <td><a class="pnr" href="{{ route('invoices.show', $a->refund->invoice_id) }}">{{ $a->refund->refund_no }}</a></td>
                            <td>Refund of invoice {{ optional($a->refund->invoice)->invoice_no }}</td>
                        @else
                            <td class="strong">Opening balance</td><td>—</td>
                        @endif
                        <td class="right strong">{{ number_format($a->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">Recorded as an advance; not matched to anything yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="stack">
        <section class="panel">
            <div class="panel-head"><x-icon name="coins" />Details</div>
            <div class="panel-body">
                <dl class="dl" style="margin:0">
                    <dt>Paid to</dt><dd>@if ($payment->payee_type === 'supplier')<a href="{{ route('suppliers.show', $payment->supplier_id) }}">{{ $payment->payee_name }}</a>@else{{ $payment->payee_name }} (refund)@endif</dd>
                    <dt>Date</dt><dd>{{ $payment->paid_on->format('d M Y') }}</dd>
                    <dt>Paid from</dt><dd>{{ optional($payment->account)->name }}</dd>
                    <dt>Reference</dt><dd>{{ $payment->reference ?: '—' }}</dd>
                    <dt>Amount</dt><dd><b class="amount-out">PKR {{ number_format($payment->amount) }}</b></dd>
                    <dt>Remarks</dt><dd>{{ $payment->remarks ?: '—' }}</dd>
                </dl>
            </div>
        </section>
        <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete payment {{ $payment->payment_no }}?')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" style="width:100%;color:var(--red-d)"><x-icon name="trash" />Delete payment</button>
        </form>
    </div>
</div>
@endsection
