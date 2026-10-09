@extends('layouts.app')
@section('title', 'Booking ' . $booking->pnr)
@section('content')
@php $inv = $booking->invoice; $c = $booking->customer; @endphp

<div class="crumbs"><a href="{{ route('flights.index') }}">Bookings</a> &nbsp;/&nbsp; <a href="{{ route('flights.index') }}">Flights</a> &nbsp;/&nbsp; {{ $booking->pnr }}</div>

<div class="detail-head">
    <div class="ref">
        <div class="sub">Booking reference</div>
        <div class="ref-line">
            <span class="ref-pnr">{{ $booking->pnr }}</span>
            <x-status :value="$booking->status" />
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost" href="{{ route('flights.edit', $booking) }}">Edit</a>
        <a class="btn btn-navy" href="{{ route('flights.invoice', $booking) }}">Download invoice &amp; e-ticket (PDF)</a>
    </div>
</div>

<div class="detail-grid">
    <div class="stack">
        <section class="panel">
            <div class="route">
                <div class="route-top">
                    <span>{{ $booking->airline }} · <span class="mono">{{ $booking->flight_no }}</span>@if ($booking->aircraft) · {{ $booking->aircraft }}@endif</span>
                    <span>{{ $booking->departure_at->format('D, d M Y') }}</span>
                </div>
                <div class="route-line">
                    <div class="route-end">
                        <span class="iata">{{ $booking->origin }}</span>
                        <span class="sub">{{ $booking->origin_airport ?: 'Departure' }}</span>
                        <span class="mono strong">{{ $booking->departure_at->format('h:i A') }}</span>
                    </div>
                    <div class="route-mid"><i></i><span>{{ $booking->duration }} · {{ $booking->cabin_class }}</span></div>
                    <div class="route-end r">
                        <span class="iata">{{ $booking->destination }}</span>
                        <span class="sub">{{ $booking->destination_airport ?: 'Arrival' }}</span>
                        <span class="mono strong">{{ $booking->arrival_at->format('h:i A') }}@if (! $booking->arrival_at->isSameDay($booking->departure_at)) <span class="sub">· {{ $booking->arrival_at->format('d M') }}</span>@endif</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">Itinerary details</div>
            <div class="panel-body dl-2">
                <div class="item"><span>E-ticket no.</span><span class="mono">{{ $booking->ticket_no ?: '—' }}</span></div>
                <div class="item"><span>Passengers</span><span>{{ $booking->adults }} {{ \Illuminate\Support\Str::plural('adult', $booking->adults) }}</span></div>
                <div class="item"><span>Cabin class</span><span>{{ $booking->cabin_class }}</span></div>
                <div class="item"><span>Seat</span><span>{{ $booking->seat ?: '—' }}</span></div>
                <div class="item"><span>Baggage</span><span>{{ $booking->baggage ?: '—' }}</span></div>
                <div class="item"><span>Meal</span><span>{{ $booking->meal ?: '—' }}</span></div>
                <div class="item"><span>Departure</span><span>{{ $booking->departure_at->format('d M Y, h:i A') }}</span></div>
                <div class="item"><span>Arrival</span><span>{{ $booking->arrival_at->format('d M Y, h:i A') }}</span></div>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="panel">
            <div class="panel-head">Lead passenger</div>
            <div class="panel-body">
                <div class="person" style="margin-bottom:16px">
                    <div class="avatar">{{ $c->initials }}</div>
                    <div><a class="strong" href="{{ route('customers.show', $c) }}">{{ $c->name }}</a><div class="sub">{{ $c->nationality }}</div></div>
                </div>
                <dl class="dl" style="margin:0">
                    <dt>Phone</dt><dd>{{ $c->phone }}</dd>
                    <dt>Email</dt><dd>{{ $c->email }}</dd>
                    <dt>Passport</dt><dd class="mono">{{ $c->passport_no }}</dd>
                </dl>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">Fare</div>
            <div class="panel-body">
                <div class="sum-rows">
                    <div class="sum-row"><span>Base fare</span><span class="mono">{{ number_format($booking->base_fare) }}</span></div>
                    <div class="sum-row"><span>Taxes &amp; charges</span><span class="mono">{{ number_format($booking->taxes) }}</span></div>
                    @if ($booking->fuel_surcharge > 0)<div class="sum-row"><span>Fuel surcharge</span><span class="mono">{{ number_format($booking->fuel_surcharge) }}</span></div>@endif
                    @if ($booking->discount > 0)<div class="sum-row"><span>Discount</span><span class="mono">− {{ number_format($booking->discount) }}</span></div>@endif
                    <div class="sum-row sum-total"><span>Total</span><span class="mono">{{ $booking->currency }} {{ number_format($booking->total) }}</span></div>
                    @if ($inv)
                        <div class="sum-row"><span>Payment</span><x-pay :value="$inv->payment_status" /></div>
                        <div class="sum-row"><span>Method</span><span>{{ $inv->payment_method }}</span></div>
                        <div class="sum-row"><span>Invoice</span><span class="mono">{{ $inv->invoice_no }}</span></div>
                        <div class="sum-row"><span>Issued</span><span>{{ optional($inv->issued_on)->format('d M Y') }}</span></div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
