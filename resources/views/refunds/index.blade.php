@extends('layouts.app')
@section('title', 'Refunds')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Transactions<span class="sep"></span><b>Refunds</b></div>
        <h1>Refunds</h1>
    </div>
    <div class="actions"><a class="btn btn-red btn-lg" href="{{ route('refunds.create') }}"><x-icon name="plus" />Add refund</a></div>
</div>

<section class="panel panel-clip">
    <form class="filters" method="GET" action="{{ route('refunds.index') }}">
        <input class="input" style="width:160px" type="date" name="from" value="{{ $filters['from'] }}">
        <input class="input" style="width:160px" type="date" name="to" value="{{ $filters['to'] }}">
        <button class="btn btn-navy btn-sm">Filter</button>
        <div class="result"><span>{{ $refunds->total() }} refunds</span></div>
    </form>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Refund no</th><th>Date</th><th>Invoice</th><th>Customer</th><th>Service</th><th class="right">From supplier</th><th class="right">Our charge</th><th class="right">To customer</th><th class="right">Still to pay</th><th></th></tr></thead>
            <tbody>
            @forelse ($refunds as $r)
                <tr>
                    <td class="pnr nowrap">{{ $r->refund_no }}</td>
                    <td class="nowrap">{{ $r->refunded_on->format('d M Y') }}</td>
                    <td><a class="pnr" href="{{ route('invoices.show', $r->invoice) }}">{{ $r->invoice->invoice_no }}</a></td>
                    <td class="strong">{{ $r->invoice->customer->name }}</td>
                    <td>{{ ucfirst($r->line->service_type) }} · {{ $r->line->reference }}</td>
                    <td class="right">{{ number_format($r->supplier_refund) }}</td>
                    <td class="right">{{ number_format($r->margin) }}</td>
                    <td class="right strong">{{ number_format($r->customer_refund) }}</td>
                    <td class="right nowrap {{ $r->outstanding > 0 ? 'amount-out' : '' }}">{{ $r->outstanding > 0 ? number_format($r->outstanding) : 'Settled' }}</td>
                    <td class="right nowrap">
                        @if ($r->outstanding > 0)<a class="btn btn-ghost btn-sm" href="{{ route('payments.create', ['payee_type' => 'customer', 'customer_id' => $r->invoice->customer_id]) }}">Pay</a>@endif
                        @if ($r->paid_amount <= 0)
                            <form method="POST" action="{{ route('refunds.destroy', $r) }}" style="display:inline" onsubmit="return confirm('Delete refund {{ $r->refund_no }}?')">@csrf @method('DELETE')<button class="icon-btn" title="Delete"><x-icon name="trash" /></button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty">No refunds yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $refunds])
</section>
@endsection
