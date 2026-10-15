@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_no)
@section('content')
@php $c = $invoice->customer; @endphp

<div class="crumbs"><a href="{{ route('invoices.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('invoices.index') }}">Invoices</a><span class="sep"></span><b>{{ $invoice->invoice_no }}</b></div>

<div class="detail-head">
    <div class="ref">
        <div class="sub">Invoice</div>
        <div class="ref-line"><span class="ref-pnr" style="font-size:32px">{{ $invoice->invoice_no }}</span><x-status :value="$invoice->payment_status" /></div>
    </div>
    <div class="actions">
        @if ($invoice->balance > 0)
            <a class="btn btn-red" href="{{ route('receipts.create', ['customer_id' => $invoice->customer_id, 'invoice_id' => $invoice->id]) }}"><x-icon name="receipt" />Receive payment</a>
        @endif
        <a class="btn btn-ghost" href="{{ route('refunds.create', ['invoice_id' => $invoice->id]) }}"><x-icon name="rotate-left" />Refund</a>
        <a class="btn btn-ghost" href="{{ route('invoices.edit', $invoice) }}"><x-icon name="pen" />Edit</a>
        <a class="btn btn-navy" href="{{ route('invoices.pdf', $invoice) }}"><x-icon name="download" />Download PDF</a>
    </div>
</div>

<div class="detail-grid">
    <div class="stack">
        <section class="panel panel-clip">
            <div class="panel-head"><x-icon name="suitcase" />Services</div>
            <div class="table-wrap">
                <table class="data narrow">
                    <thead><tr><th>Service</th><th>Details</th><th>Supplier</th><th class="right">Buying</th><th class="right">Selling</th><th class="right">{{ \App\Support\Settings::get('tax_label', 'Tax') }}</th><th class="right">Total</th></tr></thead>
                    <tbody>
                    @foreach ($invoice->lines as $l)
                        @php $b = $l->serviceable; @endphp
                        <tr>
                            <td class="nowrap"><span class="svc"><x-icon :name="$l->service_type === 'flight' ? 'plane' : 'hotel'" />{{ ucfirst($l->service_type) }}</span></td>
                            <td>
                                @if ($l->service_type === 'flight' && $b)
                                    <div class="strong"><a href="{{ route('flights.show', $b) }}">{{ $b->pnr }}</a> · {{ $b->origin }} → {{ $b->destination }}</div>
                                    <div class="sub">{{ $b->airline }} {{ $b->flight_no }} · {{ optional($b->departure_at)->format('d M Y H:i') }}</div>
                                    <div class="sub">{{ $l->passenger }}{{ $b->ticket_no ? ' · Ticket ' . $b->ticket_no : '' }}</div>
                                @elseif ($b)
                                    <div class="strong"><a href="{{ route('hotels.show', $b) }}">{{ $b->booking_ref }}</a> · {{ $b->hotel_name }}</div>
                                    <div class="sub">{{ $b->city }} · {{ $b->check_in->format('d M') }} → {{ $b->check_out->format('d M Y') }} ({{ $b->nights }} {{ \Illuminate\Support\Str::plural('night', $b->nights) }})</div>
                                    <div class="sub">{{ $l->passenger }}</div>
                                @endif
                            </td>
                            <td>{{ optional($l->supplier)->name ?: '—' }}</td>
                            <td class="right nowrap">{{ number_format($l->buying_price) }}</td>
                            <td class="right nowrap">{{ number_format($l->selling_price - $l->discount + $l->service_charge) }}</td>
                            <td class="right nowrap">{{ number_format($l->tax_amount, 2) }}</td>
                            <td class="right strong nowrap">{{ number_format($l->line_total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel panel-clip">
            <div class="panel-head"><x-icon name="receipt" />Receipts against this invoice</div>
            <div class="table-wrap">
                <table class="data narrow">
                    <thead><tr><th>Receipt</th><th>Date</th><th>Mode</th><th>Reference</th><th class="right">Applied</th></tr></thead>
                    <tbody>
                    @forelse ($invoice->allocations as $a)
                        <tr class="row" data-href="{{ route('receipts.show', $a->receipt) }}">
                            <td><a class="pnr" href="{{ route('receipts.show', $a->receipt) }}">{{ $a->receipt->receipt_no }}</a></td>
                            <td>{{ $a->receipt->received_on->format('d M Y') }}</td>
                            <td>{{ optional($a->receipt->account)->name }}</td>
                            <td>{{ $a->receipt->reference ?: '—' }}</td>
                            <td class="right strong amount-in">{{ number_format($a->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No payment received yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($invoice->refunds->count())
            <section class="panel panel-clip">
                <div class="panel-head"><x-icon name="rotate-left" />Refunds</div>
                <div class="table-wrap">
                    <table class="data narrow">
                        <thead><tr><th>Refund</th><th>Date</th><th>Service</th><th class="right">From supplier</th><th class="right">Our charge</th><th class="right">To customer</th></tr></thead>
                        <tbody>
                        @foreach ($invoice->refunds as $r)
                            <tr>
                                <td class="pnr">{{ $r->refund_no }}</td>
                                <td>{{ $r->refunded_on->format('d M Y') }}</td>
                                <td>{{ ucfirst($r->line->service_type) }} · {{ $r->line->reference }}</td>
                                <td class="right">{{ number_format($r->supplier_refund) }}</td>
                                <td class="right">{{ number_format($r->margin) }}</td>
                                <td class="right strong">{{ number_format($r->customer_refund) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <div class="stack">
        <section class="panel">
            <div class="panel-head"><x-icon name="user" />Bill to</div>
            <div class="lead-body">
                <div class="person">
                    <div class="avatar">{{ $c->initials }}</div>
                    <div>
                        <a class="nm" href="{{ route('customers.show', $c) }}">{{ $c->name }}</a>
                        <div class="nat">{{ $invoice->bill_to_type === 'walk_in' ? 'Walk-in customer' : ($c->nationality ?: 'Customer') }}</div>
                    </div>
                </div>
                <dl class="dl" style="margin-top:16px">
                    <dt>Mobile</dt><dd>{{ $c->phone ?: '—' }}</dd>
                    <dt>Invoice date</dt><dd>{{ $invoice->issued_on->format('d M Y') }}</dd>
                    <dt>Due date</dt><dd>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</dd>
                    @if ($invoice->agent)<dt>Agent</dt><dd>{{ $invoice->agent->name }} · PKR {{ number_format($invoice->agent_commission) }}</dd>@endif
                </dl>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><x-icon name="coins" />Amount</div>
            <div class="panel-body">
                <div class="totals-box">
                    <div class="row"><span>Net of discount</span><b>{{ number_format($invoice->subtotal) }}</b></div>
                    <div class="row"><span>{{ \App\Support\Settings::get('tax_label', 'Tax') }}</span><b>{{ number_format($invoice->tax_total, 2) }}</b></div>
                    <div class="row grand"><span>Grand total</span><b>PKR {{ number_format($invoice->total) }}</b></div>
                    @if ($invoice->refunded_amount > 0)<div class="row"><span>Refunded</span><b>− {{ number_format($invoice->refunded_amount) }}</b></div>@endif
                    <div class="row"><span>Received</span><b class="amount-in">{{ number_format($invoice->paid_amount) }}</b></div>
                    <div class="row"><span>Balance due</span><b class="{{ $invoice->balance > 0 ? 'amount-out' : '' }}">{{ number_format($invoice->balance) }}</b></div>
                    <div class="row" style="border-top:1px solid var(--line-2);padding-top:10px"><span>Buying cost</span><b>{{ number_format($invoice->buying_total) }}</b></div>
                    <div class="row"><span>Profit (excl. tax)</span><b>{{ number_format($invoice->profit) }}</b></div>
                </div>
            </div>
        </section>

        @if ($invoice->notes)
            <section class="panel"><div class="panel-head"><x-icon name="file-lines" />Notes</div><div class="panel-body">{{ $invoice->notes }}</div></section>
        @endif

        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete invoice {{ $invoice->invoice_no }} and its bookings? This cannot be undone.')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" style="width:100%;color:var(--red-d)"><x-icon name="trash" />Delete invoice</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
