@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Directory<span class="sep"></span><b>Customers</b></div>
        <h1>Customers</h1>
    </div>
    <div class="actions">
        <a class="btn btn-red btn-lg" href="{{ route('customers.create') }}"><x-icon name="plus" />Add customer</a>
    </div>
</div>

<section class="panel panel-clip">
    <form class="filters" method="GET" action="{{ route('customers.index') }}">
        <input class="input grow" type="search" name="q" value="{{ $q }}" placeholder="Filter by name, phone, passport…">
        <div class="result">
            @if ($q) <a href="{{ route('customers.index') }}">Clear filter</a> @endif
            <span>{{ $customers->total() }} {{ \Illuminate\Support\Str::plural('customer', $customers->total()) }}</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data narrow">
            <thead>
                <tr><th>Name</th><th>Phone</th><th>Email</th><th>Passport</th><th class="right">Invoices</th><th class="right">Outstanding</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($customers as $c)
                <tr class="row" data-href="{{ route('customers.show', $c) }}">
                    <td><a class="strong" href="{{ route('customers.show', $c) }}">{{ $c->name }}</a><div class="sub">{{ $c->nationality }}</div></td>
                    <td class="nowrap">{{ $c->phone }}</td>
                    <td>{{ $c->email ?: '—' }}</td>
                    <td class="mono">{{ $c->passport_no ?: '—' }}</td>
                    <td class="right">{{ $c->invoices_count }}</td>
                    <td class="right nowrap {{ $c->outstanding > 0 ? 'amount-out' : '' }}">{{ number_format($c->outstanding) }}</td>
                    <td class="right"><a class="btn btn-ghost btn-sm" href="{{ route('invoices.create', ['customer_id' => $c->id]) }}">New invoice</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">@if ($q) No customers match “{{ $q }}”. @else No customers yet. <a href="{{ route('customers.create') }}">Add the first one</a>. @endif</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pager', ['p' => $customers])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
