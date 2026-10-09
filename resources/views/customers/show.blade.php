@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="crumbs"><a href="{{ route('customers.index') }}">Directory</a> &nbsp;/&nbsp; <a href="{{ route('customers.index') }}">Customers</a> &nbsp;/&nbsp; {{ $customer->name }}</div>

<div class="detail-head">
    <div class="person">
        <div class="avatar">{{ $customer->initials }}</div>
        <div>
            <h1>{{ $customer->name }}</h1>
            <div class="sub">{{ $customer->nationality }} · {{ $customer->flightBookings->count() }} {{ \Illuminate\Support\Str::plural('booking', $customer->flightBookings->count()) }}</div>
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-red" href="{{ route('flights.create', ['customer_id' => $customer->id]) }}">+ Book flight</a>
    </div>
</div>

<div class="detail-grid">
    <section class="panel panel-clip">
        <div class="panel-head">Flight bookings</div>
        <div class="table-wrap">
            <table class="data narrow">
                <thead><tr><th>PNR</th><th>Route</th><th>Departure</th><th class="right">Total</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($customer->flightBookings->sortByDesc('departure_at') as $b)
                    <tr class="row" data-href="{{ route('flights.show', $b) }}">
                        <td><a class="pnr" href="{{ route('flights.show', $b) }}">{{ $b->pnr }}</a></td>
                        <td><div class="mono strong">{{ $b->origin }} → {{ $b->destination }}</div><div class="sub">{{ $b->airline }} {{ $b->flight_no }}</div></td>
                        <td>{{ $b->departure_at->format('d M Y') }}<div class="sub mono">{{ $b->departure_at->format('H:i') }}</div></td>
                        <td class="right mono strong nowrap"><span class="muted">{{ $b->currency }}</span> {{ number_format($b->total) }}</td>
                        <td><x-status :value="$b->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No bookings yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">Contact &amp; travel document</div>
        <div class="panel-body">
            <dl class="dl" style="margin:0">
                <dt>Phone</dt><dd>{{ $customer->phone }}</dd>
                <dt>Email</dt><dd>{{ $customer->email }}</dd>
                <dt>Passport</dt><dd class="mono">{{ $customer->passport_no }}</dd>
                <dt>Nationality</dt><dd>{{ $customer->nationality }}</dd>
                <dt>Address</dt><dd>{{ $customer->address ?: '—' }}</dd>
            </dl>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
