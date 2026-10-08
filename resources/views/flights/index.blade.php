@extends('layouts.app')
@section('title', 'Flight Bookings')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4>Flight Bookings</h4>
    <a href="{{ route('flights.create') }}" class="btn btn-danger">+ New Flight Booking</a>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>PNR</th><th>Customer</th><th>Flight</th><th>Route</th><th>Departure</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($bookings as $b)
        <tr>
            <td><a href="{{ route('flights.show', $b) }}">{{ $b->pnr }}</a></td>
            <td>{{ $b->customer->name }}</td>
            <td>{{ $b->airline }} {{ $b->flight_no }}</td>
            <td>{{ $b->origin }} → {{ $b->destination }}</td>
            <td>{{ $b->departure_at->format('d-m-Y H:i') }}</td>
            <td>{{ $b->currency }} {{ number_format($b->total) }}</td>
            <td><span class="badge bg-{{ $b->status === 'confirmed' ? 'success' : 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted">No bookings yet.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
