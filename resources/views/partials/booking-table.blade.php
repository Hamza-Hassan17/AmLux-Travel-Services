<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>PNR</th><th>Customer</th><th>Flight</th><th>Route</th><th>Departure</th>
                <th class="right">Pax</th><th class="right">Total</th><th>Payment</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($rows as $b)
            <tr class="row" data-href="{{ route('flights.show', $b) }}">
                <td><a class="pnr" href="{{ route('flights.show', $b) }}">{{ $b->pnr }}</a></td>
                <td><div class="strong">{{ $b->customer->name }}</div><div class="sub">{{ $b->customer->phone }}</div></td>
                <td><div>{{ $b->airline }}</div><div class="sub">{{ $b->flight_no }}</div></td>
                <td><div class="strong">{{ $b->origin }} → {{ $b->destination }}</div><div class="sub">{{ $b->duration }}</div></td>
                <td><div>{{ $b->departure_at->format('d M Y') }}</div><div class="sub">{{ $b->departure_at->format('h:i A') }}</div></td>
                <td class="right">{{ $b->adults }}</td>
                <td class="right strong nowrap"><span class="muted">{{ $b->currency }}</span> {{ number_format($b->total) }}</td>
                <td><x-pay :value="optional($b->invoice)->payment_status" /></td>
                <td><x-status :value="$b->status" /></td>
            </tr>
        @empty
            <tr><td colspan="9" class="empty">{{ $empty ?? 'No bookings yet.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
