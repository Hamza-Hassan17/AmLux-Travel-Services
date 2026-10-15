@extends('layouts.app')
@section('title', 'Receipts')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Transactions<span class="sep"></span><b>Receipts</b></div>
        <h1>Receipts</h1>
    </div>
    <div class="actions"><a class="btn btn-red btn-lg" href="{{ route('receipts.create') }}"><x-icon name="plus" />Add receipt</a></div>
</div>

<section class="panel panel-clip">
    <form class="filters" method="GET" action="{{ route('receipts.index') }}">
        <input class="input" style="width:160px" type="date" name="from" value="{{ $filters['from'] }}">
        <input class="input" style="width:160px" type="date" name="to" value="{{ $filters['to'] }}">
        <input class="input grow" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Receipt no, customer, reference…">
        <button class="btn btn-navy btn-sm">Search</button>
        <div class="result"><span>{{ $receipts->total() }} receipts · PKR {{ number_format($total) }}</span></div>
    </form>
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Receipt no</th><th>Date</th><th>Customer</th><th>Mode</th><th>Reference</th><th class="right">Amount</th><th class="right">Advance</th><th></th></tr></thead>
            <tbody>
            @forelse ($receipts as $r)
                <tr class="row" data-href="{{ route('receipts.show', $r) }}">
                    <td class="nowrap"><a class="pnr" href="{{ route('receipts.show', $r) }}">{{ $r->receipt_no }}</a></td>
                    <td class="nowrap">{{ $r->received_on->format('d M Y') }}</td>
                    <td class="strong">{{ $r->customer->name }}</td>
                    <td>{{ optional($r->account)->name }}</td>
                    <td>{{ $r->reference ?: '—' }}</td>
                    <td class="right strong amount-in nowrap">{{ number_format($r->amount) }}</td>
                    <td class="right nowrap">{{ $r->unallocated > 0 ? number_format($r->unallocated) : '—' }}</td>
                    <td class="right nowrap"><a class="icon-btn" href="{{ route('receipts.pdf', $r) }}" title="Download PDF"><x-icon name="print" /></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No receipts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $receipts])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
