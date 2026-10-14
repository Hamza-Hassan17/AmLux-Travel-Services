<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 30px 35px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c2b4a; }
    .navy { color: #0a2a6b; } .red { color: #e31e2d; font-weight: bold; } .green { color: #1fa550; font-weight: bold; }
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
    .hotelbar { background: #0e4aa0; color: #fff; padding: 7px 10px; font-weight: bold; }
    .stars { color: #e7a400; }
    .footer { background: #0a2a6b; color: #fff; padding: 7px; margin-top: 18px; text-align: center; font-size: 10px; }
    .pb { page-break-before: always; }
    ul { margin: 4px 0 0 0; padding-left: 16px; }
</style>
</head>
<body>
@php $agency = \App\Support\Settings::all(); @endphp
@php
    $c = $booking->customer;
    $inv = $booking->invoice;
    $stars = str_repeat('★', $booking->star_rating);
    $nights = $booking->nights;
    $stay = $booking->rate_per_night * $nights * $booking->rooms;
    $paid = $inv->payment_status === 'paid';
@endphp

{{-- ============ INVOICE (page 1) ============ --}}
<table><tr>
    <td><div class="navy" style="font-size:20px;font-weight:bold;">{{ $agency['name'] }}</div><i>{{ $agency['tagline'] }}</i></td>
    <td class="r"><div class="navy" style="font-weight:bold;">YOUR JOURNEY</div><i class="red">Our Priority • {{ $agency['tagline'] }}</i></td>
</tr></table>
<div style="height:10px;"></div>
<div class="title">HOTEL BOOKING INVOICE <span class="badge" style="background:{{ $paid ? '#1fa550' : '#e31e2d' }};">{{ $paid ? 'PAYMENT CONFIRMED & PAID' : 'PAYMENT PENDING' }}</span></div>

<table class="meta" style="margin-top:10px;"><tr>
    <td><div class="l">Invoice number</div><b class="navy">{{ $inv->invoice_no }}</b></td>
    <td><div class="l">Booking reference</div><b class="red">{{ $booking->booking_ref }}</b></td>
    <td><div class="l">Invoice date</div><b class="navy">{{ $inv->issued_on->format('d-m-Y') }}</b></td>
    <td><div class="l">Payment status</div><b class="{{ $paid ? 'green' : 'red' }}">{{ $paid ? 'PAID (Full Settlement)' : 'UNPAID' }}</b></td>
</tr></table>

<table style="margin-top:12px;"><tr>
<td width="50%" valign="top" style="padding-right:6px;">
    <table class="box"><tr><th colspan="2">1.1 Guest &amp; Customer Information</th></tr>
        <tr><td>Lead Guest</td><td class="r"><b>{{ $c->name }}</b></td></tr>
        <tr><td>Contact Phone</td><td class="r"><b>{{ $c->phone }}</b></td></tr>
        <tr><td>Email Address</td><td class="r"><b>{{ $c->email }}</b></td></tr>
        <tr><td>Passport / ID</td><td class="r"><b>{{ $c->passport_no }} ({{ $c->nationality }})</b></td></tr>
        <tr><td>Billing Address</td><td class="r"><b>{{ $c->address ?: '-' }}</b></td></tr>
    </table>
</td>
<td width="50%" valign="top" style="padding-left:6px;">
    <table class="box"><tr><th colspan="2">2.1 Travel Agency Details</th></tr>
        <tr><td>Agency Name</td><td class="r"><b>{{ $agency['name'] }}</b></td></tr>
        <tr><td>Head Office</td><td class="r"><b>{{ $agency['head_office'] }}</b></td></tr>
        <tr><td>Direct Helpline</td><td class="r"><b>{{ $agency['helpline'] }}</b></td></tr>
        <tr><td>Official Email</td><td class="r"><b>{{ $agency['email'] }}</b></td></tr>
        <tr><td>Web Portal</td><td class="r"><b>{{ $agency['website'] }}</b></td></tr>
    </table>
</td></tr></table>

<div class="hotelbar" style="margin-top:12px;">Hotel Property: {{ $booking->hotel_name }} &nbsp;&nbsp; City: {{ $booking->city }}{{ $booking->country ? ', ' . $booking->country : '' }}
    <span style="float:right;">{{ $booking->room_type }} • {{ $nights }} {{ \Illuminate\Support\Str::plural('Night', $nights) }}</span></div>
<table class="box" style="margin-top:0;border-top:0;">
    <tr>
        <td width="55%" valign="top">
            <div class="navy" style="font-size:16px;font-weight:bold;">{{ $booking->hotel_name }}</div>
            <div class="stars">{{ $stars }}</div>
            <div style="color:#667;">{{ $booking->address }}</div>
            <div style="margin-top:6px;"><b>Occupancy:</b> {{ $booking->guests_label }} @if ($booking->rooms > 1)| {{ $booking->rooms }} Rooms @endif @if ($booking->meal_plan)| <b>Meal Basis:</b> {{ $booking->meal_plan }}@endif</div>
        </td>
        <td width="45%" valign="top">
            <table>
                <tr><td>Check-in Date</td><td class="r"><b class="navy">{{ $booking->check_in->format('d-m-Y') }} (from {{ $booking->check_in_time }})</b></td></tr>
                <tr><td>Check-out Date</td><td class="r"><b class="navy">{{ $booking->check_out->format('d-m-Y') }} (by {{ $booking->check_out_time }})</b></td></tr>
                <tr><td>Total Duration</td><td class="r"><b class="navy">{{ $nights }} Nights / {{ $nights + 1 }} Days</b></td></tr>
            </table>
        </td>
    </tr>
    <tr style="text-align:center;background:#f6f8fc;">
        <td><small>ROOM TYPE</small><br><b>{{ $booking->room_type }}</b></td>
        <td><small>BED CONFIGURATION / STATUS</small><br><b>{{ $booking->bed_config ?: '-' }}</b> &nbsp;•&nbsp; <b class="{{ $booking->status === 'confirmed' ? 'green' : 'red' }}">{{ $booking->status === 'confirmed' ? 'Guaranteed & Confirmed' : ucfirst($booking->status) }}</b></td>
    </tr>
</table>

<table class="items" style="margin-top:12px;">
    <tr><th>Description / Stay Details</th><th>Duration</th><th class="r">Rate / Night ({{ $booking->currency }})</th><th class="r">Total Amount ({{ $booking->currency }})</th></tr>
    <tr>
        <td><b>{{ $booking->hotel_name }} - {{ $booking->room_type }} ({{ $booking->guests_label }})</b><br>
            <small>Stay Period: {{ $booking->check_in->format('d-M-Y') }} to {{ $booking->check_out->format('d-M-Y') }}@if ($booking->meal_plan) | {{ $booking->meal_plan }}@endif</small></td>
        <td>{{ $nights }} {{ \Illuminate\Support\Str::plural('Night', $nights) }}@if ($booking->rooms > 1) × {{ $booking->rooms }}@endif</td>
        <td class="r">{{ number_format($booking->rate_per_night) }}</td>
        <td class="r">{{ number_format($stay) }}</td>
    </tr>
    <tr><td>Municipality Room Fees &amp; Tourism Charges ({{ $booking->city }})</td><td>{{ $nights }} Nights</td><td class="r">{{ $booking->taxes > 0 ? '' : 'Included' }}</td><td class="r">{{ $booking->taxes > 0 ? number_format($booking->taxes) : 'Included' }}</td></tr>
    <tr><td>Service Charge &amp; Applicable Taxes</td><td>{{ $nights }} Nights</td><td class="r">Included</td><td class="r">Included</td></tr>
    @if ($booking->discount > 0)
        <tr><td>Discount / Promo</td><td></td><td class="r"></td><td class="r">- {{ number_format($booking->discount) }}</td></tr>
    @endif
</table>

<table style="margin-top:12px;"><tr>
<td width="55%" valign="top"><b class="navy">RESERVATION TERMS &amp; POLICY</b>
    <ul>
        <li>Early check-in and late check-out are subject to availability upon arrival.</li>
        <li>Government-issued original passport and security pre-authorization card required at check-in.</li>
        <li>Changes or cancellations are governed by hotel and AmLux corporate policies.</li>
    </ul></td>
<td width="45%" valign="top"><table class="box">
    <tr><td>Standard Tariff ({{ $nights }} Nights)</td><td class="r"><b>{{ $booking->currency }} {{ number_format($stay) }}</b></td></tr>
    <tr><td>Taxes &amp; Fees</td><td class="r"><b>{{ $booking->taxes > 0 ? $booking->currency . ' ' . number_format($booking->taxes) : 'Included in Rate' }}</b></td></tr>
    <tr><td>Payment Mode</td><td class="r"><b class="{{ $paid ? 'green' : 'red' }}">{{ $paid ? 'Paid in Full' : 'Unpaid' }}</b></td></tr>
    <tr><td class="total"><b>TOTAL AMOUNT</b></td><td class="r total red">{{ $booking->currency }} {{ number_format($booking->total) }}</td></tr>
</table></td></tr></table>

<table class="box" style="margin-top:12px;"><tr>
    <td width="15%" align="center"><img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->generate($booking->booking_ref . '|' . $inv->invoice_no)) }}" width="80" height="80"></td>
    <td valign="middle"><b class="navy">Official Hotel Reservation Verification • {{ $agency['name'] }}</b><br>Scan using your smartphone camera to access instant hotel voucher confirmation, front-desk concierge record and direct reservation details.</td>
</tr></table>

<div class="footer">{{ $agency['helpline'] }} &nbsp;|&nbsp; {{ $agency['mobile'] }} &nbsp;|&nbsp; {{ $agency['email'] }} &nbsp;|&nbsp; {{ $agency['website'] }} &nbsp;|&nbsp; Travel Beyond Limits</div>

{{-- ============ VOUCHER (page 2) ============ --}}
<div class="pb"></div>
<table><tr>
    <td><div class="navy" style="font-size:20px;font-weight:bold;">{{ $agency['name'] }}</div><i>Hotel Check-in Voucher</i></td>
    <td class="r"><div class="navy" style="font-weight:bold;">YOUR JOURNEY</div><i class="red">Our Priority • Official Hotel Check-in Voucher</i></td>
</tr></table>
<div style="height:10px;"></div>
<div class="title">HOTEL CONFIRMATION VOUCHER <span class="badge" style="background:#2563eb;">{{ $booking->status === 'confirmed' ? 'PREPAID / GUARANTEED STAY' : strtoupper($booking->status) }}</span></div>

<table class="meta" style="margin-top:10px;"><tr>
    <td><div class="l">Voucher number</div><b class="navy">{{ $booking->voucher_no ?: 'VCH-' . $booking->id }}</b></td>
    <td><div class="l">Hotel confirmation no</div><b class="red">{{ $booking->confirmation_no ?: '-' }}</b></td>
    <td><div class="l">Issued date</div><b class="navy">{{ $inv->issued_on->format('d-m-Y') }}</b></td>
    <td><div class="l">Billing arrangement</div><b class="green">{{ $paid ? 'Prepaid by AmLux Travel' : 'Payable at hotel / pending' }}</b></td>
</tr></table>

<div class="hotelbar" style="margin-top:12px;background:#0a2a6b;">{{ strtoupper($booking->hotel_name) }}, {{ strtoupper($booking->city) }} <span class="stars">{{ $stars }}</span>
    <span style="float:right;font-weight:normal;">Please present this voucher at reception</span></div>
<table class="box" style="margin-top:0;border-top:0;"><tr>
    <td width="62%" valign="top">
        <div class="navy" style="font-size:16px;font-weight:bold;">{{ $booking->hotel_name }}</div>
        <div style="color:#667;">{{ $booking->address }}{{ $booking->country ? ', ' . $booking->country : '' }}</div>
        <table style="margin-top:8px;">
            <tr><td><small>PRIMARY GUEST NAME</small><br><b>{{ $c->name }}</b></td><td><small>TOTAL GUESTS</small><br><b>{{ $booking->guests_label }}</b></td></tr>
            <tr><td><small>CHECK-IN DATE</small><br><b class="navy">{{ $booking->check_in->format('d-M-Y') }} (from {{ $booking->check_in_time }})</b></td><td><small>CHECK-OUT DATE</small><br><b class="navy">{{ $booking->check_out->format('d-M-Y') }} (by {{ $booking->check_out_time }})</b></td></tr>
            <tr><td><small>ROOM CATEGORY</small><br><b>{{ $booking->room_type }}</b></td><td><small>LENGTH OF STAY</small><br><b>{{ $nights }} Nights / {{ $nights + 1 }} Days</b></td></tr>
            <tr><td><small>MEAL INCLUSIONS</small><br><b class="green">{{ $booking->meal_plan ?: '-' }}</b></td><td><small>SPECIAL REQUEST</small><br><b>{{ $booking->special_request ?: '-' }}</b></td></tr>
        </table>
    </td>
    <td width="38%" valign="middle" align="center" style="border:1px solid #9be0b4;background:#f3fff7;">
        <small>RESERVATION STATUS</small><br>
        <div class="green" style="font-size:18px;">{{ $booking->status === 'confirmed' ? 'GUARANTEED' : strtoupper($booking->status) }}</div>
        <small>{{ $paid ? 'All room charges & taxes prepaid' : 'Payment pending' }}</small><br><br>
        <small>HOTEL CRS CODE:</small><br><b>{{ $booking->crs_code ?: '-' }}</b>
    </td>
</tr></table>

@if ($booking->amenity_list)
    <table class="box" style="margin-top:0;border-top:0;background:#f6f8fc;"><tr><td>
        @foreach ($booking->amenity_list as $a) <span style="margin-right:14px;">&#10003; {{ $a }}</span> @endforeach
    </td></tr></table>
@endif

<table style="margin-top:12px;"><tr>
<td width="50%" valign="top" style="padding-right:6px;">
    <table class="box"><tr><th colspan="2">1 Registered Guest Details</th></tr>
        <tr><td>Lead Guest</td><td class="r"><b>{{ $c->name }}</b></td></tr>
        <tr><td>Nationality</td><td class="r"><b>{{ $c->nationality }}</b></td></tr>
        <tr><td>Passport Number</td><td class="r"><b>{{ $c->passport_no }}</b></td></tr>
        <tr><td>Contact Number</td><td class="r"><b>{{ $c->phone }}</b></td></tr>
    </table>
</td>
<td width="50%" valign="top" style="padding-left:6px;">
    <table class="box"><tr><th colspan="2">2 24/7 Concierge &amp; Hotel Direct Support</th></tr>
        <tr><td>Hotel Direct Line</td><td class="r"><b>{{ $booking->hotel_phone ?: '-' }}</b></td></tr>
        <tr><td>Hotel Email</td><td class="r"><b>{{ $booking->hotel_email ?: '-' }}</b></td></tr>
        <tr><td>AmLux Concierge</td><td class="r"><b>{{ $agency['helpline'] }}</b></td></tr>
        <tr><td>Emergency Helpline</td><td class="r"><b>{{ $agency['mobile'] }}</b></td></tr>
    </table>
</td></tr></table>

<table style="margin-top:12px;"><tr>
    <td width="33%" valign="top"><b class="navy">1. CHECK-IN REQUIREMENTS</b><br>Please present this voucher along with original passports of all staying guests at the front reception upon arrival.</td>
    <td width="33%" valign="top"><b class="navy">2. INCIDENTALS &amp; SECURITY DEPOSIT</b><br>{{ $paid ? 'Room charges and taxes are fully prepaid.' : 'Room charges are payable as per the invoice.' }} The hotel may request a credit card or cash deposit for personal incidentals.</td>
    <td width="33%" valign="top"><b class="navy">3. CHECK-IN &amp; CHECK-OUT HOURS</b><br>Standard check-in begins at {{ $booking->check_in_time }} hrs. Check-out is strictly until {{ $booking->check_out_time }} hrs on {{ $booking->check_out->format('d-M-Y') }}.</td>
</tr></table>

<table class="box" style="margin-top:12px;"><tr>
    <td width="15%" align="center"><img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->generate(($booking->confirmation_no ?: $booking->booking_ref) . '|' . $booking->voucher_no)) }}" width="80" height="80"></td>
    <td valign="middle"><b class="navy">Digital Voucher Verification • Hotel Desk Concierge</b><br>Scan to verify the guaranteed room allocation, guest profile and direct booking confirmation under {{ $agency['name'] }}.</td>
</tr></table>

<div class="footer">{{ $agency['helpline'] }} &nbsp;|&nbsp; {{ $agency['mobile'] }} &nbsp;|&nbsp; {{ $agency['reservations_email'] }} &nbsp;|&nbsp; {{ $agency['website'] }} &nbsp;|&nbsp; {{ $agency['name'] }}</div>
</body>
</html>
