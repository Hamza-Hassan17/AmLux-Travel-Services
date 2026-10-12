@extends('layouts.app')
@section('title', 'Booking ' . $booking->pnr)
@section('content')
@php
    use App\Support\Airports;
    $inv = $booking->invoice;
    $c = $booking->customer;
    $originCity = Airports::city($booking->origin, $booking->origin_airport);
    $destCity = Airports::city($booking->destination, $booking->destination_airport);
    $sameDay = $booking->arrival_at->isSameDay($booking->departure_at);
    $carrier = Airports::carrierCode($booking->flight_no, $booking->airline);
@endphp

<div class="detail">
    <div class="detail-left">
        <div class="crumbs">
            <a href="{{ route('flights.index') }}">Bookings</a><span class="sep"></span>
            <a href="{{ route('flights.index') }}">Flights</a><span class="sep"></span>
            <b>{{ $booking->pnr }}</b>
        </div>

        <div class="ref">
            <div class="lbl">Booking Reference</div>
            <div class="ref-line">
                <span class="ref-pnr">{{ $booking->pnr }}</span>
                <x-status :value="$booking->status" />
            </div>
        </div>

        <section class="panel flight">
            <div class="flight-top">
                <div class="al-tile" style="background: {{ Airports::carrierColor($booking->airline) }}"><b>{{ $carrier }}</b><span>{{ \Illuminate\Support\Str::limit($booking->airline, 12, '') }}</span></div>
                <div>
                    <div class="al-name">{{ $booking->airline }}</div>
                    <div class="al-sub">{{ $booking->flight_no }}@if ($booking->aircraft)<i>•</i>{{ $booking->aircraft }}@endif</div>
                </div>
                <div class="flight-meta">
                    <span class="fdate"><x-icon name="calendar-days" />{{ $booking->departure_at->format('D, d M Y') }}</span>
                    <span class="cabin-pill">{{ $booking->cabin_class }}</span>
                </div>
            </div>

            <div class="flight-route">
                <img class="sk-l" src="{{ asset('img/skyline-left.svg') }}" alt="">
                <img class="sk-r" src="{{ asset('img/skyline-right.svg') }}" alt="">
                <div class="rp">
                    <span class="code">{{ $booking->origin }}</span>
                    @if ($originCity)<span class="city">{{ $originCity }}</span>@endif
                    @if ($booking->origin_airport)<span class="apt">{{ $booking->origin_airport }}</span>@endif
                    <span class="time">{{ $booking->departure_at->format('h:i A') }}</span>
                </div>
                <div class="rmid">
                    <div class="line"><span class="plane"><x-icon name="plane" /></span></div>
                    <div class="dur">{{ $booking->duration }}<i>•</i>{{ $booking->cabin_class }}</div>
                </div>
                <div class="rp r">
                    <span class="code">{{ $booking->destination }}</span>
                    @if ($destCity)<span class="city">{{ $destCity }}</span>@endif
                    @if ($booking->destination_airport)<span class="apt">{{ $booking->destination_airport }}</span>@endif
                    <span class="time">{{ $booking->arrival_at->format('h:i A') }}@if (! $sameDay)<small>+ {{ $booking->arrival_at->format('d M') }}</small>@endif</span>
                </div>
            </div>
        </section>

        <section class="panel itin">
            <div class="panel-head"><x-icon name="suitcase" />Itinerary Details</div>
            <div class="itin-grid">
                <div class="it"><span class="it-ic"><x-icon name="ticket" /></span><span class="it-t"><span class="l">E-ticket no.</span><span class="v">{{ $booking->ticket_no ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="user" /></span><span class="it-t"><span class="l">Passengers</span><span class="v">{{ $booking->adults }} {{ \Illuminate\Support\Str::plural('adult', $booking->adults) }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="chair" /></span><span class="it-t"><span class="l">Cabin class</span><span class="v">{{ $booking->cabin_class }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="chair" /></span><span class="it-t"><span class="l">Seat</span><span class="v">{{ $booking->seat ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="suitcase" /></span><span class="it-t"><span class="l">Baggage</span><span class="v">{{ $booking->baggage ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="utensils" /></span><span class="it-t"><span class="l">Meal</span><span class="v">{{ $booking->meal ?: '—' }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="calendar-days" /></span><span class="it-t"><span class="l">Departure</span><span class="v">{{ $booking->departure_at->format('d M Y, h:i A') }}</span></span></div>
                <div class="it"><span class="it-ic"><x-icon name="calendar-days" /></span><span class="it-t"><span class="l">Arrival</span><span class="v">{{ $booking->arrival_at->format('d M Y, h:i A') }}</span></span></div>
            </div>
        </section>
    </div>

    <div class="detail-right">
        <div class="top-actions">
            <button type="button" class="btn btn-ghost" onclick="window.print()"><x-icon name="print" />Print</button>
            <a class="btn btn-navy" href="{{ route('flights.invoice', $booking) }}"><x-icon name="download" />Download Invoice &amp; e-ticket (PDF)</a>
        </div>

        <section class="panel">
            <div class="panel-head"><x-icon name="user" />Lead Passenger</div>
            <div class="lead-body">
                <div class="person">
                    <div class="avatar">{{ $c->initials }}</div>
                    <div>
                        <a class="nm" href="{{ route('customers.show', $c) }}">{{ $c->name }}</a>
                        <div class="nat">{{ $c->nationality }}@include('partials.flag', ['nationality' => $c->nationality])</div>
                    </div>
                </div>
                <dl class="contact">
                    <x-icon name="phone" /><dt>Phone</dt><dd>{{ $c->phone }}</dd>
                    <x-icon name="envelope" /><dt>Email</dt><dd>{{ $c->email }}</dd>
                    <x-icon name="id-badge" /><dt>Passport</dt><dd>{{ $c->passport_no }}</dd>
                </dl>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><x-icon name="coins" />Fare</div>
            <div class="fare-body">
                <div class="fr"><span>Base fare</span><span class="v">{{ number_format($booking->base_fare) }}</span></div>
                <div class="fr"><span>Taxes &amp; charges</span><span class="v">{{ number_format($booking->taxes) }}</span></div>
                @if ($booking->fuel_surcharge > 0)<div class="fr"><span>Fuel surcharge</span><span class="v">{{ number_format($booking->fuel_surcharge) }}</span></div>@endif
                @if ($booking->discount > 0)<div class="fr"><span>Discount</span><span class="v">− {{ number_format($booking->discount) }}</span></div>@endif
                <div class="fr fr-total"><span>Total</span><span class="v">{{ $booking->currency }}&nbsp; {{ number_format($booking->total) }}</span></div>
                @if ($inv)
                    <div class="fr-sep"></div>
                    <div class="fr fr-pay"><x-icon name="credit-card" /><span>Payment</span><span class="v"><x-pay :value="$inv->payment_status" /></span></div>
                    <div class="fr"><span>Method</span><span class="v">{{ $inv->payment_method }}</span></div>
                    <div class="fr"><span>Issued</span><span class="v">{{ optional($inv->issued_on)->format('d M Y') }}</span></div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
