<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 30px 35px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c2b4a; }
    .navy { color: #0a2a6b; } .red { color: #e31e2d; font-weight: bold; } .green { color: #1fa550; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; }
    .title { background: #0a2a6b; color: #fff; padding: 12px 15px; font-size: 16px; font-weight: bold; }
    .badge { float: right; color: #fff; padding: 3px 10px; font-size: 10px; }
    .box { border: 1px solid #dde3ee; margin-top: 12px; }
    .box th { background: #f1f4f9; text-align: left; padding: 6px 8px; color: #0a2a6b; }
    .box td { padding: 4px 8px; }
    .items th { background: #0a2a6b; color: #fff; padding: 8px; text-align: left; font-size: 10px; }
    .items td { padding: 8px; border-bottom: 1px solid #e5e9f2; vertical-align: top; }
    .r { text-align: right; }
    small { color: #667; }
    .footer { background: #0a2a6b; color: #fff; padding: 7px; margin-top: 18px; text-align: center; font-size: 10px; }
</style>
</head>
<body>
@php
    $agency = \App\Support\Settings::all();
    $c = $invoice->customer;
    $taxLabel = $agency['tax_label'] ?: 'Tax';
    $paid = $invoice->payment_status === 'paid';
    $badge = ['paid' => ['PAID', '#1fa550'], 'partial' => ['PARTLY PAID', '#d88a0b'], 'unpaid' => ['UNPAID', '#e31e2d'], 'cancelled' => ['CANCELLED', '#667085']][$invoice->payment_status] ?? ['', '#667085'];
@endphp

<table><tr>
    <td><div class="navy" style="font-size:20px;font-weight:bold;">{{ $agency['name'] }}</div><i>{{ $agency['tagline'] }}</i><br>
        <small>{{ $agency['head_office'] }}<br>{{ $agency['helpline'] }} &middot; {{ $agency['email'] }}</small></td>
    <td class="r"><div class="navy" style="font-size:18px;font-weight:bold;">INVOICE</div>
        <b class="red">{{ $invoice->invoice_no }}</b><br><small>Invoice date: {{ $invoice->issued_on->format('d-m-Y') }}</small><br><small>Due date: {{ optional($invoice->due_date)->format('d-m-Y') ?: '-' }}</small></td>
</tr></table>

<div style="height:10px;"></div>
<div class="title">TAX INVOICE <span class="badge" style="background:{{ $badge[1] }};">{{ $badge[0] }}</span></div>

<table style="margin-top:12px;"><tr>
<td width="50%" valign="top" style="padding-right:6px;">
    <table class="box"><tr><th colspan="2">Billed to</th></tr>
        <tr><td colspan="2"><b class="navy" style="font-size:13px;">{{ $c->name }}</b></td></tr>
        <tr><td>Mobile</td><td class="r"><b>{{ $c->phone ?: '-' }}</b></td></tr>
        @if ($c->email)<tr><td>Email</td><td class="r"><b>{{ $c->email }}</b></td></tr>@endif
        @if ($c->tax_no)<tr><td>{{ $taxLabel }} no.</td><td class="r"><b>{{ $c->tax_no }}</b></td></tr>@endif
        @if ($c->address)<tr><td>Address</td><td class="r"><b>{{ $c->address }}</b></td></tr>@endif
    </table>
</td>
<td width="50%" valign="top" style="padding-left:6px;">
    <table class="box"><tr><th colspan="2">Invoice details</th></tr>
        <tr><td>Invoice no.</td><td class="r"><b>{{ $invoice->invoice_no }}</b></td></tr>
        <tr><td>Invoice date</td><td class="r"><b>{{ $invoice->issued_on->format('d M Y') }}</b></td></tr>
        <tr><td>Due date</td><td class="r"><b>{{ optional($invoice->due_date)->format('d M Y') ?: '-' }}</b></td></tr>
        <tr><td>Amount due</td><td class="r"><b class="{{ $invoice->balance > 0 ? 'red' : 'green' }}">PKR {{ number_format($invoice->balance) }}</b></td></tr>
    </table>
</td></tr></table>

<table class="items" style="margin-top:14px;">
    <tr><th width="14%">Services</th><th>Details</th><th class="r" width="9%">{{ $taxLabel }} %</th><th class="r" width="12%">{{ $taxLabel }}</th><th class="r" width="15%">Amount (PKR)</th></tr>
    @foreach ($invoice->lines as $l)
        @php $b = $l->serviceable; @endphp
        <tr>
            <td><b>{{ $l->service_type === 'flight' ? 'Flight Ticket' : 'Hotel' }}</b></td>
            <td>
                @if ($l->service_type === 'flight' && $b)
                    <b>PNR {{ $b->pnr }}</b>@if ($b->ticket_no) &middot; Ticket {{ $b->ticket_no }}@endif<br>
                    Sector {{ $b->origin }}/{{ $b->destination }} &middot; {{ optional($b->departure_at)->format('d-m-Y H:i') }}<br>
                    {{ $b->airline }} {{ $b->flight_no }}<br>
                    <small>Passenger: {{ $l->passenger }}</small>
                @elseif ($b)
                    <b>{{ $b->hotel_name }}</b>, {{ $b->city }} &middot; Ref {{ $b->booking_ref }}<br>
                    {{ $b->check_in->format('d-M-Y') }} to {{ $b->check_out->format('d-M-Y') }} ({{ $b->nights }} {{ \Illuminate\Support\Str::plural('night', $b->nights) }}, {{ $b->rooms }} {{ \Illuminate\Support\Str::plural('room', $b->rooms) }})<br>
                    <small>Guest: {{ $l->passenger }}</small>
                @endif
                @if ($l->description)<br><small>{{ $l->description }}</small>@endif
                @if ($l->discount > 0)<br><small>Discount: PKR {{ number_format($l->discount) }}</small>@endif
            </td>
            <td class="r">{{ $l->tax_mode === 'none' ? '-' : rtrim(rtrim(number_format($l->tax_rate, 2), '0'), '.') }}</td>
            <td class="r">{{ number_format($l->tax_amount, 2) }}</td>
            <td class="r"><b>{{ number_format($l->line_total, 2) }}</b></td>
        </tr>
    @endforeach
</table>

<table style="margin-top:12px;"><tr>
<td width="55%" valign="top">
    @if ($agency['bank_name'] || $agency['bank_account_no'])
        <table class="box" style="margin-top:0;"><tr><th colspan="2">Bank details</th></tr>
            @if ($agency['bank_name'])<tr><td>Bank</td><td class="r"><b>{{ $agency['bank_name'] }}</b></td></tr>@endif
            @if ($agency['bank_account_title'])<tr><td>Account title</td><td class="r"><b>{{ $agency['bank_account_title'] }}</b></td></tr>@endif
            @if ($agency['bank_account_no'])<tr><td>Account no.</td><td class="r"><b>{{ $agency['bank_account_no'] }}</b></td></tr>@endif
            @if ($agency['bank_iban'])<tr><td>IBAN</td><td class="r"><b>{{ $agency['bank_iban'] }}</b></td></tr>@endif
        </table>
    @endif
</td>
<td width="45%" valign="top" style="padding-left:6px;">
    <table class="box" style="margin-top:0;">
        <tr><td>Total (net of {{ strtolower($taxLabel) }})</td><td class="r">{{ number_format($invoice->total - $invoice->tax_total, 2) }}</td></tr>
        <tr><td>{{ $taxLabel }}</td><td class="r">{{ number_format($invoice->tax_total, 2) }}</td></tr>
        <tr><td style="font-size:14px;"><b>Grand total</b></td><td class="r red" style="font-size:14px;">PKR {{ number_format($invoice->total, 2) }}</td></tr>
        @if ($invoice->refunded_amount > 0)<tr><td>Refunded</td><td class="r">- {{ number_format($invoice->refunded_amount, 2) }}</td></tr>@endif
        <tr><td>Received</td><td class="r green">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
        <tr><td><b>Balance due</b></td><td class="r"><b>{{ number_format($invoice->balance, 2) }}</b></td></tr>
    </table>
</td></tr></table>

<table style="margin-top:14px;"><tr>
<td width="60%" valign="top">
    @if ($invoice->notes)<b class="navy">Note</b><br>{{ $invoice->notes }}<br><br>@endif
    <b class="navy">Terms &amp; conditions</b><br><small>{{ $agency['invoice_terms'] }}</small>
</td>
<td width="40%" align="right" valign="top">
    <img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(90)->generate($invoice->invoice_no . '|' . number_format($invoice->total, 2, '.', '') . '|' . $invoice->issued_on->format('Y-m-d'))) }}" width="90" height="90"><br>
    <br><br>
    <div style="border-top:1px solid #1c2b4a;display:inline-block;padding-top:4px;min-width:150px;text-align:center;"><small>Authorized signatory</small><br><b>{{ $agency['signatory'] ?: $agency['name'] }}</b></div>
</td></tr></table>

<div class="footer">{{ $agency['helpline'] }} &nbsp;|&nbsp; {{ $agency['mobile'] }} &nbsp;|&nbsp; {{ $agency['email'] }} &nbsp;|&nbsp; {{ $agency['website'] }}</div>
</body>
</html>
