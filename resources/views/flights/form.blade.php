@extends('layouts.app')
@section('title', $booking->exists ? 'Edit Flight Booking' : 'New Flight Booking')
@php
    $v = function ($k, $default = null) use ($booking) {
        return old($k, data_get($booking, $k, $default));
    };
    $dt = function ($k) use ($booking) {
        $val = old($k, optional($booking->$k)->format('Y-m-d\TH:i'));
        return $val;
    };
    $inv = $booking->invoice;
@endphp
@section('content')
<form method="POST" action="{{ $booking->exists ? route('flights.update', $booking) : route('flights.store') }}">
    @csrf
    @if ($booking->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header">Customer &amp; Booking Reference</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Customer *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select customer</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected($v('customer_id') == $c->id)>{{ $c->name }} ({{ $c->passport_no }})</option>
                    @endforeach
                </select>
                <a href="{{ route('customers.create') }}" class="small">+ New customer</a>
            </div>
            <div class="col-md-3"><label class="form-label">PNR *</label><input name="pnr" class="form-control text-uppercase" value="{{ $v('pnr') }}" required></div>
            <div class="col-md-3"><label class="form-label">E-Ticket No.</label><input name="ticket_no" class="form-control" value="{{ $v('ticket_no') }}"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Flight Details</div>
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Airline *</label><input name="airline" class="form-control" value="{{ $v('airline') }}" required></div>
            <div class="col-md-4"><label class="form-label">Flight No. *</label><input name="flight_no" class="form-control" value="{{ $v('flight_no') }}" required></div>
            <div class="col-md-4"><label class="form-label">Aircraft</label><input name="aircraft" class="form-control" value="{{ $v('aircraft') }}"></div>

            <div class="col-md-2"><label class="form-label">From (IATA) *</label><input name="origin" maxlength="3" class="form-control text-uppercase" value="{{ $v('origin') }}" required></div>
            <div class="col-md-4"><label class="form-label">Departure airport</label><input name="origin_airport" class="form-control" value="{{ $v('origin_airport') }}" placeholder="Jinnah Int'l, Karachi"></div>
            <div class="col-md-2"><label class="form-label">To (IATA) *</label><input name="destination" maxlength="3" class="form-control text-uppercase" value="{{ $v('destination') }}" required></div>
            <div class="col-md-4"><label class="form-label">Arrival airport</label><input name="destination_airport" class="form-control" value="{{ $v('destination_airport') }}" placeholder="Dubai Int'l, Terminal 3"></div>

            <div class="col-md-3"><label class="form-label">Departure *</label><input type="datetime-local" name="departure_at" class="form-control" value="{{ $dt('departure_at') }}" required></div>
            <div class="col-md-3"><label class="form-label">Arrival *</label><input type="datetime-local" name="arrival_at" class="form-control" value="{{ $dt('arrival_at') }}" required></div>
            <div class="col-md-3">
                <label class="form-label">Class *</label>
                <select name="cabin_class" class="form-select">
                    @foreach (['Economy', 'Premium Economy', 'Business', 'First'] as $cl)
                        <option @selected($v('cabin_class') === $cl)>{{ $cl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Passengers (adults) *</label><input type="number" name="adults" min="1" max="9" class="form-control" value="{{ $v('adults', 1) }}" required></div>

            <div class="col-md-3"><label class="form-label">Seat</label><input name="seat" class="form-control" value="{{ $v('seat') }}"></div>
            <div class="col-md-5"><label class="form-label">Baggage</label><input name="baggage" class="form-control" value="{{ $v('baggage') }}" placeholder="30 KG Check-in + 7 KG Cabin"></div>
            <div class="col-md-4"><label class="form-label">Meal</label><input name="meal" class="form-control" value="{{ $v('meal') }}"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Fare &amp; Payment</div>
        <div class="card-body row g-3">
            <div class="col-md-2"><label class="form-label">Currency *</label><input name="currency" maxlength="3" class="form-control text-uppercase" value="{{ $v('currency', 'PKR') }}" required></div>
            <div class="col-md-3"><label class="form-label">Base fare *</label><input type="number" step="0.01" min="0" name="base_fare" class="form-control" value="{{ $v('base_fare') }}" required></div>
            <div class="col-md-3"><label class="form-label">Taxes &amp; charges</label><input type="number" step="0.01" min="0" name="taxes" class="form-control" value="{{ $v('taxes', 0) }}"></div>
            <div class="col-md-2"><label class="form-label">Fuel surcharge</label><input type="number" step="0.01" min="0" name="fuel_surcharge" class="form-control" value="{{ $v('fuel_surcharge', 0) }}"></div>
            <div class="col-md-2"><label class="form-label">Discount</label><input type="number" step="0.01" min="0" name="discount" class="form-control" value="{{ $v('discount', 0) }}"></div>

            <div class="col-md-3">
                <label class="form-label">Ticket status</label>
                <select name="status" class="form-select">
                    @foreach (['confirmed', 'pending', 'cancelled'] as $s)
                        <option value="{{ $s }}" @selected($v('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment status</label>
                <select name="payment_status" class="form-select">
                    @foreach (['paid', 'unpaid'] as $s)
                        <option value="{{ $s }}" @selected(old('payment_status', optional($inv)->payment_status ?? 'paid') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Payment method</label><input name="payment_method" class="form-control" value="{{ old('payment_method', optional($inv)->payment_method ?? 'Online / Paid (100%)') }}"></div>
            <div class="col-md-3"><label class="form-label">Invoice date</label><input type="date" name="issued_on" class="form-control" value="{{ old('issued_on', optional(optional($inv)->issued_on)->format('Y-m-d') ?? date('Y-m-d')) }}"></div>
            <div class="col-12 text-muted small">Total = base fare + taxes + fuel surcharge − discount (calculated on save).</div>
        </div>
    </div>

    <button class="btn btn-primary">{{ $booking->exists ? 'Update Booking' : 'Save Booking' }}</button>
    <a href="{{ route('flights.index') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
@endsection
