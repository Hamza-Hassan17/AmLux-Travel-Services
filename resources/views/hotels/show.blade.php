@extends('layouts.app')
@section('title', 'Booking ' . $booking->booking_ref)
@section('content')
@php
    $inv = $booking->invoice;
    $line = $booking->line;
    $c = $booking->customer;
    $stars = str_repeat('★', $booking->star_rating) . str_repeat('☆', 5 - $booking->star_rating);
    $placeLine = trim($booking->city . ($booking->country ? ', ' . $booking->country : ''));
@endphp

<div class="detail">
    <div class="detail-left">
        <div class="crumbs">
            <a href="{{ route('hotels.index') }}">Bookings</a><span class="sep"></span>
            <a href="{{ route('hotels.index') }}">Hotels</a><span class="sep"></span>
            <b>{{ $booking->booking_ref }}</b>
        </div>

        <div class="ref">
            <div class="lbl">Booking Reference</div>
            <div class="ref-line">
                <span class="ref-pnr">{{ $booking->booking_ref }}</span>
                <x-status :value="$booking->status" />
            </div>
        </div>

        <section class="panel flight">
            <div class="flight-top">
                <div class="al-tile hotel-tile"><x-icon name="hotel" /></div>
                <div>
                    <div class="al-name">{{ $booking->hotel_name }}</div>
                    <div class="al-sub"><span class="stars">{{ $stars }}</span><i>•</i>{{ $placeLine }}</div>
                </div>
                <div class="flight-meta">
                    <span class="cabin-pill">{{ $booking->room_type }}</span>
                </div>
            </div>

            <div class="flight-route">
                <img class="sk-l" src="{{ asset('img/skyline-left.svg') }}" alt="">
                <img class="sk-r" src="{{ asset('img/skyline-right.svg') }}" alt="">
                <div class="rp">
                    <span class="code">{{ $booking->check_in->format('d M') }}</span>
                    <span class="city">Check-in · {{ $booking->check_in->format('l') }}</span>
                    <span class="apt">from {{ $booking->check_in_time }}</span>
                    <span class="time">{{ $booking->check_in->format('d-m-Y') }}</span>
                </div>
                <div class="rmid">
                    <div class="line"><span class="plane"><x-icon name="moon" /></span></div>
                    <div class="dur">{{ $booking->nights }} {{ \Illuminate\Support\Str::plural('Night', $booking->nights) }}<i>•</i>{{ $booking->nights + 1 }} Days</div>
                </div>
                <div class="rp r">
                    <span class="code">{{ $booking->check_out->format('d M') }}</span>
                    <span class="city">Check-out · {{ $booking->check_out->format('l') }}</span>
                    <span class="apt">by {{ $booking->check_out_time }}</span>
                    <span class="time">{{ $booking->check_out->format('d-m-Y') }}</span>
                </div>
            </div>
        </section>

        <section class="panel itin">
            <div class="panel-head"><x-icon name="bed" />Stay Details</div>
            <div class="itin-grid">
                <div class="it"><span class="it-ic"><x-icon name="ticket" /></span><span class="it-t"><span class="l">Hotel confirmation no.</span><span class="v">{{ $booking->confirmation_no ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="users" /></span><span class="it-t"><span class="l">Guests</span><span class="v">{{ $booking->guests_label }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="door-open" /></span><span class="it-t"><span class="l">Room type</span><span class="v">{{ $booking->room_type }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="bed" /></span><span class="it-t"><span class="l">Bed configuration</span><span class="v">{{ $booking->bed_config ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="building" /></span><span class="it-t"><span class="l">Rooms</span><span class="v">{{ $booking->rooms }} {{ \Illuminate\Support\Str::plural('room', $booking->rooms) }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="utensils" /></span><span class="it-t"><span class="l">Meal plan</span><span class="v">{{ $booking->meal_plan ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="location-dot" /></span><span class="it-t"><span class="l">Address</span><span class="v">{{ $booking->address ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="star" /></span><span class="it-t"><span class="l">Special request</span><span class="v">{{ $booking->special_request ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="phone" /></span><span class="it-t"><span class="l">Hotel phone</span><span class="v">{{ $booking->hotel_phone ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="envelope" /></span><span class="it-t"><span class="l">Hotel email</span><span class="v">{{ $booking->hotel_email ?: '—' }}</span></span></div>
            </div>
            @if ($booking->amenity_list)
                <div class="amenities">
                    @foreach ($booking->amenity_list as $a)<span><x-icon name="circle-check" />{{ $a }}</span>@endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="detail-right">
        <div class="top-actions">
            <button type="button" class="btn btn-ghost" onclick="window.print()"><x-icon name="print" />Print</button>
            <a class="btn btn-navy" href="{{ route('hotels.invoice', $booking) }}"><x-icon name="download" />Download voucher (PDF)</a>
        </div>

        <section class="panel">
            <div class="panel-head"><x-icon name="user" />Lead Guest</div>
            <div class="lead-body">
                <div class="person">
                    <div class="avatar">{{ $c->initials }}</div>
                    <div>
                        <span class="nm">{{ $booking->guest }}</span>
                        <div class="nat">Billed to <a href="{{ route('customers.show', $c) }}">{{ $c->name }}</a></div>
                    </div>
                </div>
                <dl class="contact">
                    <x-icon name="phone" /><dt>Phone</dt><dd>{{ $booking->guest_mobile ?: $c->phone }}</dd>
                    <x-icon name="envelope" /><dt>Email</dt><dd>{{ $c->email ?: '—' }}</dd>
                    <x-icon name="id-badge" /><dt>Passport</dt><dd>{{ $c->passport_no ?: '—' }}</dd>
                </dl>
            </div>
        </section>

                <section class="panel">
            <div class="panel-head"><x-icon name="coins" />Billing</div>
            <div class="fare-body">
                @if ($line)
                    <div class="fr"><span>Selling price</span><span class="v">{{ number_format($line->selling_price) }}</span></div>
                    @if ($line->discount > 0)<div class="fr"><span>Discount</span><span class="v">− {{ number_format($line->discount) }}</span></div>@endif
                    @if ($line->service_charge > 0)<div class="fr"><span>Service charge</span><span class="v">{{ number_format($line->service_charge) }}</span></div>@endif
                    @if ($line->tax_amount > 0)<div class="fr"><span>{{ \App\Support\Settings::get('tax_label', 'Tax') }}</span><span class="v">{{ number_format($line->tax_amount) }}</span></div>@endif
                    <div class="fr fr-total"><span>Total</span><span class="v">PKR&nbsp; {{ number_format($line->line_total) }}</span></div>
                @else
                    <div class="fr fr-total"><span>Total</span><span class="v">{{ $booking->currency }}&nbsp; {{ number_format($booking->total) }}</span></div>
                @endif
                @if ($inv)
                    <div class="fr-sep"></div>
                    <div class="fr fr-pay"><x-icon name="credit-card" /><span>Payment</span><span class="v"><x-pay :value="$inv->payment_status" /></span></div>
                    <div class="fr"><span>Invoice</span><span class="v"><a href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_no }}</a></span></div>
                    <div class="fr"><span>Issued</span><span class="v">{{ optional($inv->issued_on)->format('d M Y') }}</span></div>
                    <div class="fr"><span>Balance due</span><span class="v">PKR {{ number_format($inv->balance) }}</span></div>
                @endif
            </div>
        </section>

        <div class="top-actions">
            <a class="btn btn-ghost" style="flex:1" href="{{ route('hotels.edit', $booking) }}"><x-icon name="pen" />Edit stay</a>
            @if ($inv)<a class="btn btn-ghost" style="flex:1" href="{{ route('invoices.show', $inv) }}"><x-icon name="file-invoice" />View invoice</a>@endif
        </div>
    </div>
</div>
@endsection
