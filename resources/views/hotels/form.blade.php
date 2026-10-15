@extends('layouts.app')
@section('title', 'Edit booking ' . $booking->booking_ref)
@php
    $v = function ($k, $default = null) use ($booking) {
        return old($k, data_get($booking, $k, $default));
    };
    $d = function ($k) use ($booking) {
        return old($k, optional($booking->$k)->format('Y-m-d'));
    };
@endphp
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('hotels.index') }}">Bookings</a><span class="sep"></span><a href="{{ route('hotels.index') }}">Hotels</a><span class="sep"></span><a href="{{ route('hotels.show', $booking) }}">{{ $booking->booking_ref }}</a><span class="sep"></span>Edit</div>
        <h1>Edit hotel stay</h1>
    </div>
</div>

@if ($booking->invoice)
    <div class="alert alert-ok" style="background:#EEF5FE;border-color:#CFE0F7;color:#0B3B8C">
        Prices, supplier and payment are managed on invoice
        <a href="{{ route('invoices.edit', $booking->invoice) }}"><b>{{ $booking->invoice->invoice_no }}</b></a>. This page edits the stay details only.
    </div>
@endif

<form method="POST" action="{{ route('hotels.update', $booking) }}">
    @csrf @method('PUT')
    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><x-icon name="hotel" />Hotel details</div>
                <div class="panel-body grid">
                    <div class="field c4"><label for="booking_ref">Booking reference <span class="req">*</span></label><input id="booking_ref" name="booking_ref" class="input upper" value="{{ $v('booking_ref') }}" required></div>
                    <div class="field c4"><label for="confirmation_no">Hotel confirmation no.</label><input id="confirmation_no" name="confirmation_no" class="input" value="{{ $v('confirmation_no') }}"></div>
                    <div class="field c4"><label for="crs_code">Hotel CRS code</label><input id="crs_code" name="crs_code" class="input" value="{{ $v('crs_code') }}"></div>

                    <div class="field c6"><label for="hotel_name">Hotel name <span class="req">*</span></label><input id="hotel_name" name="hotel_name" class="input" value="{{ $v('hotel_name') }}" required></div>
                    <div class="field c2">
                        <label for="star_rating">Stars</label>
                        <select id="star_rating" name="star_rating" class="select">
                            @foreach ([5, 4, 3, 2, 1] as $n)<option value="{{ $n }}" {{ (int) $v('star_rating') === $n ? 'selected' : '' }}>{{ $n }} ★</option>@endforeach
                        </select>
                    </div>
                    <div class="field c2"><label for="city">City <span class="req">*</span></label><input id="city" name="city" class="input" value="{{ $v('city') }}" required></div>
                    <div class="field c2"><label for="country">Country</label><input id="country" name="country" class="input" value="{{ $v('country') }}"></div>

                    <div class="field c6"><label for="address">Address</label><input id="address" name="address" class="input" value="{{ $v('address') }}"></div>
                    <div class="field c3"><label for="hotel_phone">Hotel phone</label><input id="hotel_phone" name="hotel_phone" class="input" value="{{ $v('hotel_phone') }}"></div>
                    <div class="field c3"><label for="hotel_email">Hotel email</label><input id="hotel_email" type="email" name="hotel_email" class="input" value="{{ $v('hotel_email') }}"></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="bed" />Stay &amp; room</div>
                <div class="panel-body grid">
                    <div class="field c3"><label for="check_in">Check-in date <span class="req">*</span></label><input id="check_in" type="date" name="check_in" class="input" value="{{ $d('check_in') }}" required></div>
                    <div class="field c3"><label for="check_in_time">Check-in from</label><input id="check_in_time" type="time" name="check_in_time" class="input" value="{{ $v('check_in_time', '15:00') }}" required></div>
                    <div class="field c3"><label for="check_out">Check-out date <span class="req">*</span></label><input id="check_out" type="date" name="check_out" class="input" value="{{ $d('check_out') }}" required></div>
                    <div class="field c3"><label for="check_out_time">Check-out by</label><input id="check_out_time" type="time" name="check_out_time" class="input" value="{{ $v('check_out_time', '12:00') }}" required></div>

                    <div class="field c6"><label for="room_type">Room type <span class="req">*</span></label><input id="room_type" name="room_type" class="input" value="{{ $v('room_type') }}" required></div>
                    <div class="field c6"><label for="bed_config">Bed configuration</label><input id="bed_config" name="bed_config" class="input" value="{{ $v('bed_config') }}"></div>

                    <div class="field c2"><label for="rooms">Rooms</label><input id="rooms" type="number" name="rooms" min="1" max="20" class="input" value="{{ $v('rooms', 1) }}" required></div>
                    <div class="field c2"><label for="adults">Adults</label><input id="adults" type="number" name="adults" min="1" max="20" class="input" value="{{ $v('adults', 2) }}" required></div>
                    <div class="field c2"><label for="children">Children</label><input id="children" type="number" name="children" min="0" max="20" class="input" value="{{ $v('children', 0) }}"></div>
                    <div class="field c6"><label for="meal_plan">Meal plan</label><input id="meal_plan" name="meal_plan" class="input" value="{{ $v('meal_plan') }}"></div>

                    <div class="field c6"><label for="guest_name">Guest name</label><input id="guest_name" name="guest_name" class="input" value="{{ $v('guest_name') }}"></div>
                    <div class="field c3"><label for="guest_mobile">Guest mobile</label><input id="guest_mobile" name="guest_mobile" class="input" value="{{ $v('guest_mobile') }}"></div>
                    <div class="field c3">
                        <label for="status">Reservation status</label>
                        <select id="status" name="status" class="select">
                            @foreach (['confirmed', 'pending', 'cancelled'] as $s)<option value="{{ $s }}" {{ $v('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach
                        </select>
                    </div>

                    <div class="field c12"><label for="special_request">Special request</label><input id="special_request" name="special_request" class="input" value="{{ $v('special_request') }}"></div>
                    <div class="field c12"><label for="amenities">Included amenities <span class="muted">(one per line, shown on the voucher)</span></label><textarea id="amenities" name="amenities" class="textarea" rows="3">{{ $v('amenities') }}</textarea></div>
                </div>
            </section>
        </div>

        <aside class="panel summary">
            <div class="panel-head"><x-icon name="hotel" />Actions</div>
            <div class="panel-body">
                <div class="sum-actions" style="margin-top:0">
                    <button class="btn btn-red btn-lg">Update stay</button>
                    <a href="{{ route('hotels.show', $booking) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@endsection
