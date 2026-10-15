@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Transactions<span class="sep"></span><b>Payments</b></div>
        <h1>Payments</h1>
    </div>
    <div class="actions"><a class="btn btn-red btn-lg" href="{{ route('payments.create') }}"><x-icon name="plus" />Add payment</a></div>
</div>

<section class="panel panel-clip">
    <form class="filters" method="GET" action="{{ route('payments.index') }}">
        <input class="input" style="width:160px" type="date" name="from" value="{{ $filters['from'] }}">
        <input class="input" style="width:160px" type="date" name="to" value="{{ $filters['to'] }}">
        <input class="input grow" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Payment no, supplier, customer…">
        <button class="btn btn-navy btn-sm">Search</button>
        <div class="result"><span>{{ $payments->total() }} payments · PKR {{ number_format($total) }}</span></div>
    </form>
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Payment no</th><th>Date</th><th>Paid to</th><th>Type</th><th>Mode</th><th>Reference</th><th class="right">Amount</th><th></th></tr></thead>
            <tbody>
            @forelse ($payments as $p)
                <tr class="row" data-href="{{ route('payments.show', $p) }}">
                    <td class="nowrap"><a class="pnr" href="{{ route('payments.show', $p) }}">{{ $p->payment_no }}</a></td>
                    <td class="nowrap">{{ $p->paid_on->format('d M Y') }}</td>
                    <td class="strong">{{ $p->payee_name }}</td>
                    <td>{{ $p->payee_type === 'supplier' ? 'Supplier' : 'Customer refund' }}</td>
                    <td>{{ optional($p->account)->name }}</td>
                    <td>{{ $p->reference ?: '—' }}</td>
                    <td class="right strong amount-out nowrap">{{ number_format($p->amount) }}</td>
                    <td class="right"><a class="icon-btn" href="{{ route('payments.pdf', $p) }}" title="Download voucher"><x-icon name="print" /></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No payments yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $payments])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
