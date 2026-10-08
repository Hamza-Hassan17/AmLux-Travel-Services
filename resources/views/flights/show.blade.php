@extends('layouts.app')
@section('title', 'Flight ' . $booking->pnr)
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>1.3 Flight Booking Details</span>
        <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : 'secondary' }}">{{ ucfirst($booking->status) }}</span>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><th width="30%">PNR No.</th><td>{{ $booking->pnr }}</td></tr>
            <tr><th>E-Ticket No.</th><td>{{ $booking->ticket_no }}</td></tr>
            <tr><th>Passenger</th><td>{{ $booking->customer->name }} ({{ $booking->customer->passport_no }})</td></tr>
            <tr><th>Airline / Flight</th><td>{{ $booking->airline }} {{ $booking->flight_no }}</td></tr>
            <tr><th>Route</th><td>{{ $booking->origin }} → {{ $booking->destination }} ({{ $booking->duration }})</td></tr>
            <tr><th>Departure</th><td>{{ $booking->departure_at->format('d-m-Y h:i A') }}</td></tr>
            <tr><th>Arrival</th><td>{{ $booking->arrival_at->format('d-m-Y h:i A') }}</td></tr>
            <tr><th>Class / Seat</th><td>{{ $booking->cabin_class }} / {{ $booking->seat ?: '-' }}</td></tr>
            <tr><th>Fare</th><td class="fw-bold text-success">{{ $booking->currency }} {{ number_format($booking->total) }}</td></tr>
            <tr><th>Invoice</th><td>{{ $booking->invoice->invoice_no }} ({{ ucfirst($booking->invoice->payment_status) }})</td></tr>
        </table>
        <a class="btn btn-primary" href="{{ route('flights.invoice', $booking) }}">Download Invoice &amp; E-Ticket (PDF)</a>
        <a class="btn btn-outline-primary" href="{{ route('flights.edit', $booking) }}">Edit</a>
        <a class="btn btn-outline-secondary" href="{{ route('flights.index') }}">Back</a>
    </div>
</div>
@endsection
