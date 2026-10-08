@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4>Customers</h4>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">+ Add Customer</a>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Passport</th><th></th></tr></thead>
    <tbody>
    @forelse ($customers as $c)
        <tr>
            <td><a href="{{ route('customers.show', $c) }}">{{ $c->name }}</a></td>
            <td>{{ $c->phone }}</td><td>{{ $c->email }}</td><td>{{ $c->passport_no }}</td>
            <td><a class="btn btn-sm btn-danger" href="{{ route('flights.create', ['customer_id' => $c->id]) }}">Book flight</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted">No customers yet.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $customers->links() }}</div>
@endsection
