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
    .box { border: 1px solid #dde3ee; margin-top: 12px; }
    .box th { background: #f1f4f9; text-align: left; padding: 6px 8px; color: #0a2a6b; }
    .box td { padding: 5px 8px; }
    .items th { background: #0a2a6b; color: #fff; padding: 8px; text-align: left; font-size: 10px; }
    .items td { padding: 8px; border-bottom: 1px solid #e5e9f2; }
    .r { text-align: right; }
    small { color: #667; }
    .footer { background: #0a2a6b; color: #fff; padding: 7px; margin-top: 18px; text-align: center; font-size: 10px; }
</style>
</head>
<body>
@php $agency = \App\Support\Settings::all(); $c = $receipt->customer; @endphp
<table><tr>
    <td><div class="navy" style="font-size:20px;font-weight:bold;">{{ $agency['name'] }}</div><i>{{ $agency['tagline'] }}</i><br><small>{{ $agency['head_office'] }}<br>{{ $agency['helpline'] }} &middot; {{ $agency['email'] }}</small></td>
    <td class="r"><div class="navy" style="font-size:18px;font-weight:bold;">RECEIPT</div><b class="red">{{ $receipt->receipt_no }}</b><br><small>Date: {{ $receipt->received_on->format('d-m-Y') }}</small></td>
</tr></table>
<div style="height:10px;"></div>
<div class="title">PAYMENT RECEIPT</div>

<table style="margin-top:12px;"><tr>
<td width="50%" valign="top" style="padding-right:6px;">
    <table class="box"><tr><th colspan="2">Received from</th></tr>
        <tr><td colspan="2"><b class="navy" style="font-size:13px;">{{ $c->name }}</b></td></tr>
        <tr><td>Mobile</td><td class="r"><b>{{ $c->phone ?: '-' }}</b></td></tr>
    </table>
</td>
<td width="50%" valign="top" style="padding-left:6px;">
    <table class="box"><tr><th colspan="2">Receipt details</th></tr>
        <tr><td>Receipt no.</td><td class="r"><b>{{ $receipt->receipt_no }}</b></td></tr>
        <tr><td>Receipt date</td><td class="r"><b>{{ $receipt->received_on->format('d M Y') }}</b></td></tr>
        <tr><td>Mode of payment</td><td class="r"><b>{{ optional($receipt->account)->name }}</b></td></tr>
        @if ($receipt->reference)<tr><td>Reference</td><td class="r"><b>{{ $receipt->reference }}</b></td></tr>@endif
    </table>
</td></tr></table>

<table class="items" style="margin-top:14px;">
    <tr><th>Reference no.</th><th>Services</th><th>Passenger / guest</th><th class="r">Amount (PKR)</th></tr>
    @foreach ($receipt->allocations as $a)
        @if ($a->invoice)
            <tr>
                <td><b>{{ $a->invoice->invoice_no }}</b></td>
                <td>{{ $a->invoice->lines->map(function ($l) { return ucfirst($l->service_type); })->unique()->implode(', ') }}</td>
                <td>{{ $a->invoice->lines->pluck('passenger')->filter()->unique()->implode(', ') }}</td>
                <td class="r"><b>{{ number_format($a->amount, 2) }}</b></td>
            </tr>
        @else
            <tr><td><b>Opening balance</b></td><td>-</td><td>-</td><td class="r"><b>{{ number_format($a->amount, 2) }}</b></td></tr>
        @endif
    @endforeach
    @if ($receipt->unallocated > 0)
        <tr><td><b>Advance</b></td><td>On account</td><td>-</td><td class="r"><b>{{ number_format($receipt->unallocated, 2) }}</b></td></tr>
    @endif
    <tr><td colspan="3" class="r"><b>Grand total</b></td><td class="r red" style="font-size:14px;">PKR {{ number_format($receipt->amount, 2) }}</td></tr>
</table>

@if ($receipt->remarks)<p><b class="navy">Remarks:</b> {{ $receipt->remarks }}</p>@endif

<table style="margin-top:30px;"><tr>
<td width="50%" align="center"><img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->generate($receipt->receipt_no . '|' . number_format($receipt->amount, 2, '.', ''))) }}" width="80" height="80"></td>
<td width="50%" align="center" valign="bottom"><div style="border-top:1px solid #1c2b4a;display:inline-block;padding-top:4px;min-width:160px;"><small>Authorized signatory</small><br><b>{{ $agency['signatory'] ?: $agency['name'] }}</b></div></td>
</tr></table>

<div class="footer">{{ $agency['helpline'] }} &nbsp;|&nbsp; {{ $agency['mobile'] }} &nbsp;|&nbsp; {{ $agency['email'] }} &nbsp;|&nbsp; {{ $agency['website'] }}</div>
</body>
</html>
