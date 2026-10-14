@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><b>Dashboard</b></div>
        <h1>Welcome back</h1>
    </div>
    <div class="actions">
        <a class="btn btn-red btn-lg" href="{{ route('flights.create') }}"><x-icon name="plane" />New flight booking</a>
    </div>
</div>

@include('partials.kpis', ['kpis' => $kpis])

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="plane-departure" />Departing in the next 14 days</div>
    @include('partials.booking-table', ['rows' => $upcoming, 'empty' => 'No departures in the next 14 days.'])
</section>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="hotel" />Hotel check-ins in the next 14 days</div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Reference</th><th>Guest</th><th>Hotel</th><th>Stay</th><th class="right">Total</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($stays as $b)
                <tr class="row" data-href="{{ route('hotels.show', $b) }}">
                    <td><a class="pnr" href="{{ route('hotels.show', $b) }}">{{ $b->booking_ref }}</a></td>
                    <td><div class="strong">{{ $b->customer->name }}</div><div class="sub">{{ $b->customer->phone }}</div></td>
                    <td><div class="strong">{{ $b->hotel_name }}</div><div class="sub">{{ $b->city }}</div></td>
                    <td><div>{{ $b->check_in->format('d M') }} → {{ $b->check_out->format('d M Y') }}</div><div class="sub">{{ $b->nights }} {{ \Illuminate\Support\Str::plural('night', $b->nights) }}</div></td>
                    <td class="right strong nowrap"><span class="muted">{{ $b->currency }}</span> {{ number_format($b->total) }}</td>
                    <td><x-pay :value="optional($b->invoice)->payment_status" /></td>
                    <td><x-status :value="$b->status" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No hotel check-ins in the next 14 days.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel panel-clip">
    <div class="panel-head"><x-icon name="ticket" />Recent bookings</div>
    @include('partials.booking-table', ['rows' => $recent, 'empty' => 'No bookings yet.'])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
