@extends('layouts.app')
@section('title', $booking->exists ? 'Edit booking ' . $booking->booking_ref : 'New hotel booking')
@php
    $v = function ($k, $default = null) use ($booking) {
        return old($k, data_get($booking, $k, $default));
    };
    $d = function ($k) use ($booking) {
        return old($k, optional($booking->$k)->format('Y-m-d'));
    };
    $inv = $booking->invoice;
    $payStatus = old('payment_status', optional($inv)->payment_status ?? 'paid');
@endphp
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('hotels.index') }}">Bookings</a><span class="sep"></span><a href="{{ route('hotels.index') }}">Hotels</a><span class="sep"></span>{{ $booking->exists ? $booking->booking_ref : 'New' }}</div>
        <h1>{{ $booking->exists ? 'Edit hotel booking' : 'New hotel booking' }}</h1>
    </div>
</div>

<form method="POST" action="{{ $booking->exists ? route('hotels.update', $booking) : route('hotels.store') }}" id="booking-form">
    @csrf
    @if ($booking->exists) @method('PUT') @endif

    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><x-icon name="user" />Guest &amp; booking reference</div>
                <div class="panel-body grid">
                    <div class="field c6">
                        <label for="customer_id">Lead guest <span class="req">*</span></label>
                        <select id="customer_id" name="customer_id" class="select" required>
                            <option value="">Select customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" {{ $v('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->passport_no }})</option>
                            @endforeach
                        </select>
                        <a class="hint" href="{{ route('customers.create') }}">+ New customer</a>
                    </div>
                    <div class="field c3"><label for="booking_ref">Booking reference</label><input id="booking_ref" name="booking_ref" class="input upper" value="{{ $v('booking_ref') }}" placeholder="Auto-generated if blank"></div>
                    <div class="field c3"><label for="confirmation_no">Hotel confirmation no.</label><input id="confirmation_no" name="confirmation_no" class="input" value="{{ $v('confirmation_no') }}"></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="hotel" />Hotel details</div>
                <div class="panel-body grid">
                    <div class="field c6"><label for="hotel_name">Hotel name <span class="req">*</span></label><input id="hotel_name" name="hotel_name" class="input" value="{{ $v('hotel_name') }}" required></div>
                    <div class="field c3">
                        <label for="star_rating">Star rating <span class="req">*</span></label>
                        <select id="star_rating" name="star_rating" class="select">
                            @foreach ([5, 4, 3, 2, 1] as $n)
                                <option value="{{ $n }}" {{ (int) $v('star_rating') === $n ? 'selected' : '' }}>{{ $n }} {{ $n === 1 ? 'star' : 'stars' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c3"><label for="crs_code">Hotel CRS code</label><input id="crs_code" name="crs_code" class="input" value="{{ $v('crs_code') }}"></div>

                    <div class="field c4"><label for="city">City <span class="req">*</span></label><input id="city" name="city" class="input" value="{{ $v('city') }}" placeholder="Dubai" required></div>
                    <div class="field c4"><label for="country">Country</label><input id="country" name="country" class="input" value="{{ $v('country') }}" placeholder="UAE"></div>
                    <div class="field c4"><label for="hotel_phone">Hotel phone</label><input id="hotel_phone" name="hotel_phone" class="input" value="{{ $v('hotel_phone') }}"></div>

                    <div class="field c8"><label for="address">Address</label><input id="address" name="address" class="input" value="{{ $v('address') }}" placeholder="Jumeirah Beach Road, Umm Suqeim 3"></div>
                    <div class="field c4"><label for="hotel_email">Hotel email</label><input id="hotel_email" type="email" name="hotel_email" class="input" value="{{ $v('hotel_email') }}"></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="bed" />Stay &amp; room</div>
                <div class="panel-body grid">
                    <div class="field c3"><label for="check_in">Check-in date <span class="req">*</span></label><input id="check_in" type="date" name="check_in" class="input" value="{{ $d('check_in') }}" required></div>
                    <div class="field c3"><label for="check_in_time">Check-in from</label><input id="check_in_time" type="time" name="check_in_time" class="input" value="{{ $v('check_in_time', '15:00') }}" required></div>
                    <div class="field c3"><label for="check_out">Check-out date <span class="req">*</span></label><input id="check_out" type="date" name="check_out" class="input" value="{{ $d('check_out') }}" required></div>
                    <div class="field c3"><label for="check_out_time">Check-out by</label><input id="check_out_time" type="time" name="check_out_time" class="input" value="{{ $v('check_out_time', '12:00') }}" required></div>

                    <div class="field c6"><label for="room_type">Room type <span class="req">*</span></label><input id="room_type" name="room_type" class="input" value="{{ $v('room_type') }}" placeholder="Deluxe Marina View Room" required></div>
                    <div class="field c6"><label for="bed_config">Bed configuration</label><input id="bed_config" name="bed_config" class="input" value="{{ $v('bed_config') }}" placeholder="1 King Size Bed"></div>

                    <div class="field c2"><label for="rooms">Rooms <span class="req">*</span></label><input id="rooms" type="number" name="rooms" min="1" max="20" class="input" value="{{ $v('rooms', 1) }}" required></div>
                    <div class="field c2"><label for="adults">Adults <span class="req">*</span></label><input id="adults" type="number" name="adults" min="1" max="20" class="input" value="{{ $v('adults', 2) }}" required></div>
                    <div class="field c2"><label for="children">Children</label><input id="children" type="number" name="children" min="0" max="20" class="input" value="{{ $v('children', 0) }}"></div>
                    <div class="field c6"><label for="meal_plan">Meal plan</label><input id="meal_plan" name="meal_plan" class="input" value="{{ $v('meal_plan') }}" placeholder="Daily Buffet Breakfast Included"></div>

                    <div class="field c12"><label for="special_request">Special request</label><input id="special_request" name="special_request" class="input" value="{{ $v('special_request') }}" placeholder="High floor, non-smoking room"></div>
                    <div class="field c12"><label for="amenities">Included amenities <span class="muted">(one per line, shown on the voucher)</span></label><textarea id="amenities" name="amenities" class="textarea" rows="3">{{ $v('amenities') }}</textarea></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="coins" />Rate &amp; payment</div>
                <div class="panel-body grid">
                    <div class="field c2"><label for="currency">Currency <span class="req">*</span></label><input id="currency" name="currency" maxlength="3" class="input upper" value="{{ $v('currency', 'PKR') }}" required></div>
                    <div class="field c4"><label for="rate_per_night">Rate per night <span class="req">*</span></label><input id="rate_per_night" type="number" step="0.01" min="0" name="rate_per_night" class="input" value="{{ $v('rate_per_night') }}" required></div>
                    <div class="field c3"><label for="taxes">Taxes &amp; fees</label><input id="taxes" type="number" step="0.01" min="0" name="taxes" class="input" value="{{ $v('taxes', 0) }}"></div>
                    <div class="field c3"><label for="discount">Discount</label><input id="discount" type="number" step="0.01" min="0" name="discount" class="input" value="{{ $v('discount', 0) }}"></div>

                    <div class="field c3">
                        <label for="status">Reservation status</label>
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
            <div class="panel-head"><x-icon name="coins" />Rate summary</div>
            <div class="panel-body">
                <div class="sum-rows">
                    <div class="sum-row"><span>Nights</span><span class="v" id="sum-nights">—</span></div>
                    <div class="sum-row"><span>Rate / night</span><span class="v" data-sum="rate_per_night">0</span></div>
                    <div class="sum-row"><span>Room charges</span><span class="v" id="sum-rooms">0</span></div>
                    <div class="sum-row"><span>Taxes &amp; fees</span><span class="v" data-sum="taxes">0</span></div>
                    <div class="sum-row"><span>Discount</span><span class="v" data-sum="discount">0</span></div>
                    <div class="sum-row sum-total"><span>Total</span><span class="v" id="sum-total">—</span></div>
                </div>
                <p class="sub" style="margin-top:12px">Calculated on save: rate × nights × rooms + taxes − discount.</p>
                <div class="sum-actions">
                    <button class="btn btn-red">{{ $booking->exists ? 'Update booking' : 'Save booking' }}</button>
                    <a href="{{ $booking->exists ? route('hotels.show', $booking) : route('hotels.index') }}" class="btn btn-ghost">Cancel</a>
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
            var a = f.elements.check_in.value, b = f.elements.check_out.value, nights = 0;
            if (a && b) { nights = Math.max(0, Math.round((new Date(b) - new Date(a)) / 86400000)); }
            var rooms = Math.max(1, parseInt(f.elements.rooms.value, 10) || 1);
            var stay = n('rate_per_night') * nights * rooms;
            document.getElementById('sum-nights').textContent = nights || '—';
            document.getElementById('sum-rooms').textContent = fmt(stay);
            ['rate_per_night', 'taxes', 'discount'].forEach(function (k) {
                f.querySelector('[data-sum="' + k + '"]').textContent = fmt(n(k));
            });
            var total = Math.max(0, stay + n('taxes') - n('discount'));
            document.getElementById('sum-total').textContent = (f.elements.currency.value || '').toUpperCase() + ' ' + fmt(total);
        }
        ['check_in', 'check_out', 'rooms', 'rate_per_night', 'taxes', 'discount', 'currency'].forEach(function (k) {
            f.elements[k].addEventListener('input', update);
        });
        update();
    })();
</script>
@endpush
