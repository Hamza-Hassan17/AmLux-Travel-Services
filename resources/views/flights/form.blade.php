@extends('layouts.app')
@section('title', 'Edit booking ' . $booking->pnr)
@php
    $v = function ($k, $default = null) use ($booking) {
        return old($k, data_get($booking, $k, $default));
    };
    $dt = function ($k) use ($booking) {
        return old($k, optional($booking->$k)->format('Y-m-d\TH:i'));
    };
@endphp
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('flights.index') }}">Bookings</a><span class="sep"></span><a href="{{ route('flights.index') }}">Flights</a><span class="sep"></span><a href="{{ route('flights.show', $booking) }}">{{ $booking->pnr }}</a><span class="sep"></span>Edit</div>
        <h1>Edit flight itinerary</h1>
    </div>
</div>

@if ($booking->invoice)
    <div class="alert alert-ok" style="background:#EEF5FE;border-color:#CFE0F7;color:#0B3B8C">
        Prices, supplier and payment are managed on invoice
        <a href="{{ route('invoices.edit', $booking->invoice) }}"><b>{{ $booking->invoice->invoice_no }}</b></a>. This page edits the itinerary only.
    </div>
@endif

<form method="POST" action="{{ route('flights.update', $booking) }}">
    @csrf @method('PUT')
    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><x-icon name="plane" />Flight details</div>
                <div class="panel-body grid">
                    <div class="field c3"><label for="pnr">PNR <span class="req">*</span></label><input id="pnr" name="pnr" class="input upper" value="{{ $v('pnr') }}" required></div>
                    <div class="field c3"><label for="ticket_no">E-ticket no.</label><input id="ticket_no" name="ticket_no" class="input" value="{{ $v('ticket_no') }}"></div>
                    <div class="field c3"><label for="airline">Airline <span class="req">*</span></label><input id="airline" name="airline" class="input" value="{{ $v('airline') }}" required></div>
                    <div class="field c3"><label for="flight_no">Flight no. <span class="req">*</span></label><input id="flight_no" name="flight_no" class="input" value="{{ $v('flight_no') }}" required></div>

                    <div class="field c2"><label for="origin">From (IATA) <span class="req">*</span></label><input id="origin" name="origin" maxlength="3" class="input upper" value="{{ $v('origin') }}" required></div>
                    <div class="field c4"><label for="origin_airport">Departure airport</label><input id="origin_airport" name="origin_airport" class="input" value="{{ $v('origin_airport') }}"></div>
                    <div class="field c2"><label for="destination">To (IATA) <span class="req">*</span></label><input id="destination" name="destination" maxlength="3" class="input upper" value="{{ $v('destination') }}" required></div>
                    <div class="field c4"><label for="destination_airport">Arrival airport</label><input id="destination_airport" name="destination_airport" class="input" value="{{ $v('destination_airport') }}"></div>

                    <div class="field c4"><label for="departure_at">Departure <span class="req">*</span></label><input id="departure_at" type="datetime-local" name="departure_at" class="input" value="{{ $dt('departure_at') }}" required></div>
                    <div class="field c4"><label for="arrival_at">Arrival</label><input id="arrival_at" type="datetime-local" name="arrival_at" class="input" value="{{ $dt('arrival_at') }}"></div>
                    <div class="field c2">
                        <label for="cabin_class">Class</label>
                        <select id="cabin_class" name="cabin_class" class="select">
                            @foreach (['Economy', 'Premium Economy', 'Business', 'First'] as $cl)
                                <option {{ $v('cabin_class') === $cl ? 'selected' : '' }}>{{ $cl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c2"><label for="adults">Passengers</label><input id="adults" type="number" name="adults" min="1" max="9" class="input" value="{{ $v('adults', 1) }}" required></div>

                    <div class="field c6"><label for="passenger_name">Passenger name</label><input id="passenger_name" name="passenger_name" class="input" value="{{ $v('passenger_name') }}"></div>
                    <div class="field c6"><label for="passenger_mobile">Passenger mobile</label><input id="passenger_mobile" name="passenger_mobile" class="input" value="{{ $v('passenger_mobile') }}"></div>

                    <div class="field c4"><label for="aircraft">Aircraft</label><input id="aircraft" name="aircraft" class="input" value="{{ $v('aircraft') }}"></div>
                    <div class="field c2"><label for="seat">Seat</label><input id="seat" name="seat" class="input" value="{{ $v('seat') }}"></div>
                    <div class="field c3"><label for="baggage">Baggage</label><input id="baggage" name="baggage" class="input" value="{{ $v('baggage') }}"></div>
                    <div class="field c3"><label for="meal">Meal</label><input id="meal" name="meal" class="input" value="{{ $v('meal') }}"></div>
                    <div class="field c4">
                        <label for="status">Ticket status</label>
                        <select id="status" name="status" class="select">
                            @foreach (['confirmed', 'pending', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ $v('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>
        </div>

        <aside class="panel summary">
            <div class="panel-head"><x-icon name="plane" />Actions</div>
            <div class="panel-body">
                <div class="sum-actions" style="margin-top:0">
                    <button class="btn btn-red btn-lg">Update itinerary</button>
                    <a href="{{ route('flights.show', $booking) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@endsection
