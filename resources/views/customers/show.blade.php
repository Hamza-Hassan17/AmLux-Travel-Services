@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="card mb-4">
    <div class="card-header">{{ $customer->name }}</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><strong>Phone:</strong> {{ $customer->phone }}</div>
            <div class="col-md-4"><strong>Email:</strong> {{ $customer->email }}</div>
            <div class="col-md-4"><strong>Passport:</strong> {{ $customer->passport_no }} ({{ $customer->nationality }})</div>
        </div>
        <div class="mt-2"><strong>Address:</strong> {{ $customer->address ?: '-' }}</div>
        <a class="btn btn-danger mt-3" href="{{ route('flights.create', ['customer_id' => $customer->id]) }}">Book flight</a>
    </div>
</div>
<h5>Flight bookings</h5>
<ul class="list-group">
@forelse ($customer->flightBookings as $b)
    <li class="list-group-item"><a href="{{ route('flights.show', $b) }}">{{ $b->pnr }}</a> &mdash; {{ $b->origin }} → {{ $b->destination }}, {{ $b->departure_at->format('d-m-Y') }}</li>
@empty
    <li class="list-group-item text-muted">No bookings.</li>
@endforelse
</ul>
@endsection
