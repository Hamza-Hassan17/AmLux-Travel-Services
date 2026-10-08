<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 30px 35px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c2b4a; }
    .navy { color: #0a2a6b; } .red { color: #e31e2d; font-weight: bold; }
    .title { background: #0a2a6b; color: #fff; padding: 12px 15px; font-size: 16px; font-weight: bold; }
    .badge { float: right; background: #1fa550; color: #fff; padding: 3px 10px; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { border: 1px solid #dde3ee; padding: 8px; }
    .meta .l { font-size: 9px; color: #667; text-transform: uppercase; }
    .box td { padding: 4px 8px; } .box { border: 1px solid #dde3ee; margin-top: 12px; }
    .box th { background: #f1f4f9; text-align: left; padding: 6px 8px; color: #0a2a6b; }
    .items th { background: #0a2a6b; color: #fff; padding: 8px; text-align: left; }
    .items td { padding: 8px; border-bottom: 1px solid #e5e9f2; }
    .r { text-align: right; }
    .total { font-size: 15px; }
    .footer { background: #0a2a6b; color: #fff; padding: 7px; margin-top: 18px; text-align: center; font-size: 10px; }
    .pb { page-break-before: always; }
</style>
</head>
<body>
@php $c = $booking->customer; $inv = $booking->invoice; @endphp

<table><tr>
    <td><div class="navy" style="font-size:20px;font-weight:bold;">AmLux Travel Services</div><i>Indulge in luxury experiences</i></td>
    <td class="r"><div class="navy" style="font-weight:bold;">YOUR JOURNEY</div><i class="red">Our Priority • Travel Beyond Limits</i></td>
</tr></table>
<div style="height:10px;"></div>
<div class="title">FLIGHT BOOKING INVOICE <span class="badge">PAYMENT CONFIRMED</span></div>

<table class="meta" style="margin-top:10px;"><tr>
    <td><div class="l">Invoice number</div><b class="navy">{{ $inv->invoice_no }}</b></td>
    <td><div class="l">Booking ref / PNR</div><b class="red">{{ $booking->pnr }}</b></td>
    <td><div class="l">Issue date</div><b class="navy">{{ $inv->issued_on->format('d-m-Y') }}</b></td>
    <td><div class="l">Payment method</div><b class="navy">{{ $inv->payment_method }}</b></td>
</tr></table>

<table style="margin-top:12px;"><tr>
<td width="50%" valign="top" style="padding-right:6px;">
    <table class="box"><tr><th colspan="2">1 Passenger &amp; Customer Information</th></tr>
        <tr><td>Passenger Name</td><td class="r"><b>{{ $c->name }}</b></td></tr>
        <tr><td>Contact Number</td><td class="r"><b>{{ $c->phone }}</b></td></tr>
        <tr><td>Email</td><td class="r"><b>{{ $c->email }}</b></td></tr>
        <tr><td>Passport No</td><td class="r"><b>{{ $c->passport_no }} ({{ $c->nationality }})</b></td></tr>
        <tr><td>Address</td><td class="r"><b>{{ $c->address ?: '-' }}</b></td></tr>
    </table>
</td>
<td width="50%" valign="top" style="padding-left:6px;">
    <table class="box"><tr><th colspan="2">2 Service Agency Details</th></tr>
        <tr><td>Agency Name</td><td class="r"><b>AmLux Travel Services</b></td></tr>
        <tr><td>Office Address</td><td class="r"><b>Suite 402, Luxury Plaza, Karachi</b></td></tr>
        <tr><td>Direct Helpline</td><td class="r"><b>+92 21 111 2589</b></td></tr>
        <tr><td>Email</td><td class="r"><b>info@amluxtravels.com</b></td></tr>
        <tr><td>Web Portal</td><td class="r"><b>www.amluxtravels.com</b></td></tr>
    </table>
</td></tr></table>

<table class="box" style="margin-top:12px;">
    <tr><th colspan="3">{{ $booking->airline }} &middot; Flight {{ $booking->flight_no }} &middot; {{ $booking->aircraft }} &middot; {{ $booking->cabin_class }}</th></tr>
    <tr style="text-align:center;">
        <td><div class="navy" style="font-size:20px;font-weight:bold;">{{ $booking->origin }}</div><small>{{ $booking->origin_airport }}</small><br><span class="red">{{ $booking->departure_at->format('h:i A • d-M-Y') }}</span></td>
        <td>{{ $booking->duration }}<br>&#9992;</td>
        <td><div class="navy" style="font-size:20px;font-weight:bold;">{{ $booking->destination }}</div><small>{{ $booking->destination_airport }}</small><br><span class="red">{{ $booking->arrival_at->format('h:i A • d-M-Y') }}</span></td>
    </tr>
    <tr style="text-align:center;background:#f6f8fc;">
        <td><small>BAGGAGE</small><br><b>{{ $booking->baggage ?: '-' }}</b></td>
        <td><small>SEAT</small><br><b>{{ $booking->seat ?: '-' }}</b></td>
        <td><small>TICKET STATUS</small><br><b>{{ $booking->status === 'confirmed' ? 'Issued & Validated' : ucfirst($booking->status) }}</b></td>
    </tr>
</table>

<table class="items" style="margin-top:12px;">
    <tr><th>Description</th><th>Pax</th><th class="r">Amount ({{ $booking->currency }})</th></tr>
    <tr><td><b>Airfare: {{ $booking->origin }} to {{ $booking->destination }}</b><br><small>Carrier: {{ $booking->airline }} | {{ $booking->cabin_class }}</small></td><td>{{ $booking->adults }} Adult</td><td class="r">{{ number_format($booking->base_fare) }}</td></tr>
    <tr><td>Airport Security, Terminal &amp; Passenger Service Charges</td><td>{{ $booking->adults }} Adult</td><td class="r">{{ number_format($booking->taxes) }}</td></tr>
    <tr><td>Fuel Surcharge &amp; Aviation Regulatory Levies</td><td>{{ $booking->adults }} Adult</td><td class="r">{{ number_format($booking->fuel_surcharge) }}</td></tr>
</table>

<table style="margin-top:12px;"><tr>
<td width="55%" valign="top"><b class="navy">TERMS &amp; IMPORTANT INSTRUCTIONS</b>
    <ul>
        <li>Arrive at the international departure terminal at least 3.5 hours prior to departure.</li>
        <li>Passport validity must be minimum 6 months from travel date, with a valid entry permit where required.</li>
        <li>Date change and cancellation fees apply per airline tariff rules and AmLux terms.</li>
    </ul></td>
<td width="45%" valign="top"><table class="box">
    <tr><td>Subtotal Fare</td><td class="r"><b>{{ $booking->currency }} {{ number_format($booking->base_fare + $booking->taxes + $booking->fuel_surcharge) }}</b></td></tr>
    <tr><td>Discount / Promo</td><td class="r">- {{ $booking->currency }} {{ number_format($booking->discount) }}</td></tr>
    <tr><td>GST &amp; Taxes</td><td class="r">Included</td></tr>
    <tr><td class="total"><b>TOTAL PAID</b></td><td class="r total red">{{ $booking->currency }} {{ number_format($booking->total) }}</td></tr>
</table></td></tr></table>

<div class="footer">+92 21 111 2589 &nbsp;|&nbsp; +92 300 1234567 &nbsp;|&nbsp; info@amluxtravels.com &nbsp;|&nbsp; www.amluxtravels.com &nbsp;|&nbsp; Travel Beyond Limits</div>

{{-- ============ E-TICKET (page 2) ============ --}}
<div class="pb"></div>
<table><tr>
    <td><div class="navy" style="font-size:20px;font-weight:bold;">AmLux Travel Services</div><i>Electronic Ticket Passenger Receipt</i></td>
    <td class="r"><div class="navy" style="font-weight:bold;">YOUR JOURNEY</div><i class="red">Our Priority • Validated Travel Document</i></td>
</tr></table>
<div style="height:10px;"></div>
<div class="title">OFFICIAL E-TICKET PASSENGER RECEIPT <span class="badge" style="background:#e31e2d;">CONFIRMED / OK TO BOARD</span></div>

<table class="meta" style="margin-top:10px;"><tr>
    <td><div class="l">Electronic ticket no</div><b class="navy">{{ $booking->ticket_no }}</b></td>
    <td><div class="l">Booking reference (PNR)</div><b class="red">{{ $booking->pnr }}</b></td>
    <td><div class="l">Issuing airline</div><b class="navy">{{ $booking->airline }}</b></td>
    <td><div class="l">Passenger name</div><b class="navy">{{ strtoupper($c->name) }}</b></td>
</tr></table>

<table class="box" style="margin-top:12px;">
    <tr><th colspan="2">{{ $booking->airline }} &middot; Flight {{ $booking->flight_no }} &middot; BOARDING PASS</th></tr>
    <tr>
        <td width="70%" valign="top">
            <div class="navy" style="font-size:18px;font-weight:bold;">{{ $booking->origin }} &rarr; {{ $booking->destination }}</div>
            Dept: <b>{{ $booking->departure_at->format('h:i A | d-M-Y') }}</b><br>
            Arr: <b>{{ $booking->arrival_at->format('h:i A | d-M-Y') }}</b><br><br>
            Passenger: <b>{{ $c->name }}</b> &nbsp; Passport: <b>{{ $c->passport_no }}</b><br>
            Seat: <b class="red">{{ $booking->seat ?: '-' }}</b> &nbsp; Class: <b>{{ $booking->cabin_class }}</b> &nbsp; Baggage: <b>{{ $booking->baggage ?: '-' }}</b>
        </td>
        <td width="30%" align="center">
            <img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(110)->generate($booking->pnr . '|' . $booking->ticket_no)) }}" width="110" height="110"><br>
            <small>PNR: {{ $booking->pnr }}</small>
        </td>
    </tr>
</table>

<table style="margin-top:12px;"><tr>
    <td width="33%" valign="top"><b class="navy">CHECK-IN &amp; BAGGAGE DROP</b><br>Airline counters open 3.5 hours before departure. Check-in closes 60 minutes before scheduled departure.</td>
    <td width="33%" valign="top"><b class="navy">SECURITY &amp; GATE</b><br>Proceed to Security &amp; Customs after bag drop. Gates close 20 minutes before takeoff.</td>
    <td width="33%" valign="top"><b class="navy">TRAVEL DOCUMENTS</b><br>Present this e-ticket, a valid passport and any required entry permit at immigration control.</td>
</tr></table>

<div class="footer">+92 21 111 2589 &nbsp;|&nbsp; reservations@amluxtravels.com &nbsp;|&nbsp; www.amluxtravels.com &nbsp;|&nbsp; AmLux Travel Services</div>
</body>
</html>
