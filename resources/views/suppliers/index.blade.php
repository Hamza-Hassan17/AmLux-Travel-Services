@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Directory<span class="sep"></span><b>Suppliers</b></div>
        <h1>Suppliers</h1>
    </div>
    <div class="actions"><a class="btn btn-red btn-lg" href="{{ route('suppliers.create') }}"><x-icon name="plus" />Add supplier</a></div>
</div>

<section class="panel panel-clip">
    <form class="filters" method="GET" action="{{ route('suppliers.index') }}">
        <input class="input grow" type="search" name="q" value="{{ $q }}" placeholder="Filter by name or phone…">
        <div class="result"><span>{{ $suppliers->total() }} suppliers</span></div>
    </form>
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th class="right">We owe</th><th></th></tr></thead>
            <tbody>
            @forelse ($suppliers as $s)
                <tr class="row" data-href="{{ route('suppliers.show', $s) }}">
                    <td><a class="strong" href="{{ route('suppliers.show', $s) }}">{{ $s->name }}</a></td>
                    <td class="nowrap">{{ $s->phone ?: '—' }}</td>
                    <td>{{ $s->email ?: '—' }}</td>
                    <td class="right strong nowrap {{ $s->payable > 0 ? 'amount-out' : '' }}">{{ number_format($s->payable) }}</td>
                    <td class="right"><a class="btn btn-ghost btn-sm" href="{{ route('payments.create', ['payee_type' => 'supplier', 'supplier_id' => $s->id]) }}">Pay</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No suppliers yet. <a href="{{ route('suppliers.create') }}">Add the first one</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $suppliers])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
