@extends('layouts.app')
@section('title', 'Receipt ' . $receipt->receipt_no)
@section('content')
<div class="crumbs"><a href="{{ route('receipts.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('receipts.index') }}">Receipts</a><span class="sep"></span><b>{{ $receipt->receipt_no }}</b></div>

<div class="detail-head">
    <div class="ref">
        <div class="sub">Receipt</div>
        <div class="ref-line"><span class="ref-pnr" style="font-size:32px">{{ $receipt->receipt_no }}</span>
            @if ($receipt->unallocated > 0)<span class="pill pill-partial">Advance PKR {{ number_format($receipt->unallocated) }}</span>@else<x-status value="paid" />@endif</div>
    </div>
    <div class="actions">
        <a class="btn btn-navy" href="{{ route('receipts.pdf', $receipt) }}"><x-icon name="download" />Download PDF</a>
        <button class="btn btn-ghost" onclick="window.print()"><x-icon name="print" />Print</button>
    </div>
</div>

<div class="detail-grid">
    <div class="stack">
        <section class="panel panel-clip">
            <div class="panel-head"><x-icon name="file-invoice" />Applied to</div>
            <div class="table-wrap">
                <table class="data narrow">
                    <thead><tr><th>Document</th><th>Invoice date</th><th class="right">Amount</th></tr></thead>
                    <tbody>
                    @forelse ($receipt->allocations as $a)
                        <tr>
                            @if ($a->invoice)
                                <td><a class="pnr" href="{{ route('invoices.show', $a->invoice) }}">{{ $a->invoice->invoice_no }}</a></td>
                                <td>{{ $a->invoice->issued_on->format('d M Y') }}</td>
                            @else
                                <td class="strong">Opening balance</td><td>—</td>
                            @endif
                            <td class="right strong">{{ number_format($a->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">Not matched to any invoice yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($open)
            <form method="POST" action="{{ route('receipts.allocate', $receipt) }}">
                @csrf
                <section class="panel panel-clip">
                    <div class="panel-head"><x-icon name="hand-holding-dollar" />Match the remaining PKR {{ number_format($receipt->unallocated) }} to invoices</div>
                    @include('partials.alloc-invoices', ['open' => $open])
                    <div class="panel-body actions" style="border-top:1px solid var(--line)"><button class="btn btn-navy">Match to invoices</button></div>
                </section>
            </form>
        @endif
    </div>

    <div class="stack">
        <section class="panel">
            <div class="panel-head"><x-icon name="coins" />Details</div>
            <div class="panel-body">
                <dl class="dl" style="margin:0">
                    <dt>Customer</dt><dd><a href="{{ route('customers.show', $receipt->customer) }}">{{ $receipt->customer->name }}</a></dd>
                    <dt>Date</dt><dd>{{ $receipt->received_on->format('d M Y') }}</dd>
                    <dt>Mode</dt><dd>{{ optional($receipt->account)->name }}</dd>
                    <dt>Reference</dt><dd>{{ $receipt->reference ?: '—' }}</dd>
                    <dt>Amount</dt><dd><b class="amount-in">PKR {{ number_format($receipt->amount) }}</b></dd>
                    <dt>Remarks</dt><dd>{{ $receipt->remarks ?: '—' }}</dd>
                </dl>
            </div>
        </section>
        <form method="POST" action="{{ route('receipts.destroy', $receipt) }}" onsubmit="return confirm('Delete receipt {{ $receipt->receipt_no }}? Linked invoices become unpaid again.')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" style="width:100%;color:var(--red-d)"><x-icon name="trash" />Delete receipt</button>
        </form>
    </div>
</div>
@endsection
