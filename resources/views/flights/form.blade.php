@extends('layouts.app')
@section('title', $booking->exists ? 'Edit booking ' . $booking->pnr : 'New flight booking')
@php
    $v = function ($k, $default = null) use ($booking) {
        return old($k, data_get($booking, $k, $default));
    };
    $dt = function ($k) use ($booking) {
        return old($k, optional($booking->$k)->format('Y-m-d\TH:i'));
    };
    $inv = $booking->invoice;
    $payStatus = old('payment_status', optional($inv)->payment_status ?? 'paid');
@endphp
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('flights.index') }}">Bookings</a> &nbsp;/&nbsp; <a href="{{ route('flights.index') }}">Flights</a> &nbsp;/&nbsp; {{ $booking->exists ? $booking->pnr : 'New' }}</div>
        <h1>{{ $booking->exists ? 'Edit flight booking' : 'New flight booking' }}</h1>
    </div>
</div>

<form method="POST" action="{{ $booking->exists ? route('flights.update', $booking) : route('flights.store') }}" id="booking-form">
    @csrf
    @if ($booking->exists) @method('PUT') @endif

    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head">Customer &amp; booking reference</div>
                <div class="panel-body grid">
                    <div class="field c6">
                        <label for="customer_id">Customer <span class="req">*</span></label>
                        <select id="customer_id" name="customer_id" class="select" required>
                            <option value="">Select customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" {{ $v('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->passport_no }})</option>
                            @endforeach
                        </select>
                        <a class="hint" href="{{ route('customers.create') }}">+ New customer</a>
                    </div>
                    <div class="field c3"><label for="pnr">PNR <span class="req">*</span></label><input id="pnr" name="pnr" class="input mono upper" value="{{ $v('pnr') }}" required></div>
                    <div class="field c3"><label for="ticket_no">E-ticket no.</label><input id="ticket_no" name="ticket_no" class="input mono" value="{{ $v('ticket_no') }}"></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">Flight details</div>
                <div class="panel-body grid">
                    <div class="field c4"><label for="airline">Airline <span class="req">*</span></label><input id="airline" name="airline" class="input" value="{{ $v('airline') }}" required></div>
                    <div class="field c4"><label for="flight_no">Flight no. <span class="req">*</span></label><input id="flight_no" name="flight_no" class="input mono" value="{{ $v('flight_no') }}" required></div>
                    <div class="field c4"><label for="aircraft">Aircraft</label><input id="aircraft" name="aircraft" class="input" value="{{ $v('aircraft') }}"></div>

                    <div class="field c2"><label for="origin">From (IATA) <span class="req">*</span></label><input id="origin" name="origin" maxlength="3" class="input mono upper" value="{{ $v('origin') }}" required></div>
                    <div class="field c4"><label for="origin_airport">Departure airport</label><input id="origin_airport" name="origin_airport" class="input" value="{{ $v('origin_airport') }}" placeholder="Jinnah Int'l, Karachi"></div>
                    <div class="field c2"><label for="destination">To (IATA) <span class="req">*</span></label><input id="destination" name="destination" maxlength="3" class="input mono upper" value="{{ $v('destination') }}" required></div>
                    <div class="field c4"><label for="destination_airport">Arrival airport</label><input id="destination_airport" name="destination_airport" class="input" value="{{ $v('destination_airport') }}" placeholder="Dubai Int'l, Terminal 3"></div>

                    <div class="field c3"><label for="departure_at">Departure <span class="req">*</span></label><input id="departure_at" type="datetime-local" name="departure_at" class="input" value="{{ $dt('departure_at') }}" required></div>
                    <div class="field c3"><label for="arrival_at">Arrival <span class="req">*</span></label><input id="arrival_at" type="datetime-local" name="arrival_at" class="input" value="{{ $dt('arrival_at') }}" required></div>
                    <div class="field c3">
                        <label for="cabin_class">Class <span class="req">*</span></label>
                        <select id="cabin_class" name="cabin_class" class="select">
                            @foreach (['Economy', 'Premium Economy', 'Business', 'First'] as $cl)
                                <option {{ $v('cabin_class') === $cl ? 'selected' : '' }}>{{ $cl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c3"><label for="adults">Passengers (adults) <span class="req">*</span></label><input id="adults" type="number" name="adults" min="1" max="9" class="input mono" value="{{ $v('adults', 1) }}" required></div>

                    <div class="field c3"><label for="seat">Seat</label><input id="seat" name="seat" class="input" value="{{ $v('seat') }}"></div>
                    <div class="field c5"><label for="baggage">Baggage</label><input id="baggage" name="baggage" class="input" value="{{ $v('baggage') }}" placeholder="30 KG Check-in + 7 KG Cabin"></div>
                    <div class="field c4"><label for="meal">Meal</label><input id="meal" name="meal" class="input" value="{{ $v('meal') }}"></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">Fare &amp; payment</div>
                <div class="panel-body grid">
                    <div class="field c2"><label for="currency">Currency <span class="req">*</span></label><input id="currency" name="currency" maxlength="3" class="input mono upper" value="{{ $v('currency', 'PKR') }}" required></div>
                    <div class="field c3"><label for="base_fare">Base fare <span class="req">*</span></label><input id="base_fare" type="number" step="0.01" min="0" name="base_fare" class="input mono" value="{{ $v('base_fare') }}" required></div>
                    <div class="field c3"><label for="taxes">Taxes &amp; charges</label><input id="taxes" type="number" step="0.01" min="0" name="taxes" class="input mono" value="{{ $v('taxes', 0) }}"></div>
                    <div class="field c2"><label for="fuel_surcharge">Fuel surcharge</label><input id="fuel_surcharge" type="number" step="0.01" min="0" name="fuel_surcharge" class="input mono" value="{{ $v('fuel_surcharge', 0) }}"></div>
                    <div class="field c2"><label for="discount">Discount</label><input id="discount" type="number" step="0.01" min="0" name="discount" class="input mono" value="{{ $v('discount', 0) }}"></div>

                    <div class="field c3">
                        <label for="status">Ticket status</label>
                        <select id="status" name="status" class="select">
                            @foreach (['confirmed', 'pending', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ $v('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c3">
                        <label for="payment_status">Payment status</label>
                        <select id="payment_status" name="payment_status" class="select">
                            @foreach (['paid', 'unpaid'] as $s)
                                <option value="{{ $s }}" {{ $payStatus === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c3"><label for="payment_method">Payment method</label><input id="payment_method" name="payment_method" class="input" value="{{ old('payment_method', optional($inv)->payment_method ?? 'Online / Paid (100%)') }}"></div>
                    <div class="field c3"><label for="issued_on">Invoice date</label><input id="issued_on" type="date" name="issued_on" class="input" value="{{ old('issued_on', optional(optional($inv)->issued_on)->format('Y-m-d') ?? date('Y-m-d')) }}"></div>
                </div>
            </section>
        </div>

        <aside class="panel summary">
            <div class="panel-head">Fare summary</div>
            <div class="panel-body">
                <div class="sum-rows">
                    <div class="sum-row"><span>Base fare</span><span class="mono" data-sum="base_fare">0</span></div>
                    <div class="sum-row"><span>Taxes &amp; charges</span><span class="mono" data-sum="taxes">0</span></div>
                    <div class="sum-row"><span>Fuel surcharge</span><span class="mono" data-sum="fuel_surcharge">0</span></div>
                    <div class="sum-row"><span>Discount</span><span class="mono" data-sum="discount">0</span></div>
                    <div class="sum-row sum-total"><span>Total</span><span class="mono" id="sum-total">—</span></div>
                </div>
                <p class="sub" style="margin-top:12px">Calculated on save: base fare + taxes + fuel surcharge − discount.</p>
                <div class="sum-actions">
                    <button class="btn btn-red">{{ $booking->exists ? 'Update booking' : 'Save booking' }}</button>
                    <a href="{{ $booking->exists ? route('flights.show', $booking) : route('flights.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var f = document.getElementById('booking-form');
        function n(name) { return parseFloat(f.elements[name].value) || 0; }
        function fmt(x) { return x.toLocaleString('en-US', { maximumFractionDigits: 2 }); }
        function update() {
            ['base_fare', 'taxes', 'fuel_surcharge', 'discount'].forEach(function (k) {
                f.querySelector('[data-sum="' + k + '"]').textContent = fmt(n(k));
            });
            var total = n('base_fare') + n('taxes') + n('fuel_surcharge') - n('discount');
            document.getElementById('sum-total').textContent = (f.elements.currency.value || '').toUpperCase() + ' ' + fmt(total);
        }
        ['base_fare', 'taxes', 'fuel_surcharge', 'discount', 'currency'].forEach(function (k) {
            f.elements[k].addEventListener('input', update);
        });
        update();
    })();
</script>
@endpush
