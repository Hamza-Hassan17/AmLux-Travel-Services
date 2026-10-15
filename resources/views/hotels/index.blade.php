@extends('layouts.app')
@section('title', 'Hotel bookings')
@section('content')
@php
    $tabs = [
        ['key' => null, 'label' => 'All', 'n' => $counts->sum()],
        ['key' => 'confirmed', 'label' => 'Confirmed', 'n' => $counts->get('confirmed', 0)],
        ['key' => 'pending', 'label' => 'Pending', 'n' => $counts->get('pending', 0)],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'n' => $counts->get('cancelled', 0)],
    ];
    $keep = request()->except('page');
    $active = collect($filters)->only(['q', 'city', 'payment', 'from'])->filter()->isNotEmpty();
@endphp

<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('hotels.index') }}">Bookings</a><span class="sep"></span><b>Hotels</b></div>
        <h1>Hotel bookings</h1>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-lg" href="{{ route('hotels.export', $keep) }}"><x-icon name="download" />Export CSV</a>
        <a class="btn btn-red btn-lg" href="{{ route('invoices.create', ['service' => 'hotel']) }}"><x-icon name="hotel" />New hotel invoice</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi">
        <div class="kpi-label">Bookings this month</div>
        <div class="kpi-val"><b>{{ number_format($kpis['month']) }}</b></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Revenue this month</div>
        <div class="kpi-val"><span class="unit">PKR</span><b>{{ $kpis['revenue'] }}</b></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Awaiting confirmation</div>
        <div class="kpi-val"><b class="warn">{{ $kpis['pending'] }}</b></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Checking in within 7 days</div>
        <div class="kpi-val"><b>{{ $kpis['upcoming'] }}</b></div>
    </div>
</div>

<section class="panel panel-clip">
    <div class="tabs">
        @foreach ($tabs as $t)
            <a class="tab {{ ($filters['status'] ?? null) === $t['key'] ? 'on' : '' }}"
               href="{{ route('hotels.index', array_filter(array_merge($keep, ['status' => $t['key']]), fn ($v) => ! is_null($v) && $v !== '')) }}">{{ $t['label'] }}<span class="n">{{ $t['n'] }}</span></a>
        @endforeach
    </div>

    <form class="filters" method="GET" action="{{ route('hotels.index') }}" id="filter-form">
        @if ($filters['status']) <input type="hidden" name="status" value="{{ $filters['status'] }}"> @endif
        <input class="input grow" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Filter by reference, guest, hotel…">
        <select class="chip {{ $filters['city'] ? 'set' : '' }}" name="city" data-auto>
            <option value="">City</option>
            @foreach ($cities as $c)
                <option value="{{ $c }}" {{ $filters['city'] === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
        <input class="chip {{ $filters['from'] ? 'set' : '' }}" type="date" name="from" value="{{ $filters['from'] }}" title="Checking in on or after" data-auto>
        <select class="chip {{ $filters['payment'] ? 'set' : '' }}" name="payment" data-auto>
            <option value="">Payment</option>
            <option value="paid" {{ $filters['payment'] === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ $filters['payment'] === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
        <noscript><button class="btn btn-ghost btn-sm">Apply</button></noscript>
        <div class="result">
            @if ($active) <a href="{{ route('hotels.index', array_filter(['status' => $filters['status']])) }}">Clear filters</a> @endif
            <span>{{ $bookings->total() }} {{ \Illuminate\Support\Str::plural('result', $bookings->total()) }}</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Reference</th><th>Guest</th><th>Hotel</th><th>Stay</th><th>Room</th>
                    <th class="right">Guests</th><th class="right">Total</th><th>Payment</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($bookings as $b)
                <tr class="row" data-href="{{ route('hotels.show', $b) }}">
                    <td class="nowrap"><a class="pnr" href="{{ route('hotels.show', $b) }}">{{ $b->booking_ref }}</a></td>
                    <td><div class="strong">{{ $b->customer->name }}</div><div class="sub nowrap">{{ $b->customer->phone }}</div></td>
                    <td><div class="strong">{{ $b->hotel_name }}</div><div class="sub">{{ $b->city }}{{ $b->country ? ', ' . $b->country : '' }} · {{ str_repeat('★', $b->star_rating) }}</div></td>
                    <td><div>{{ $b->check_in->format('d M') }} → {{ $b->check_out->format('d M Y') }}</div><div class="sub">{{ $b->nights }} {{ \Illuminate\Support\Str::plural('night', $b->nights) }}</div></td>
                    <td><div>{{ $b->room_type }}</div><div class="sub">{{ $b->rooms }} {{ \Illuminate\Support\Str::plural('room', $b->rooms) }}</div></td>
                    <td class="right">{{ $b->adults + $b->children }}</td>
                    <td class="right strong nowrap"><span class="muted">{{ $b->currency }}</span> {{ number_format($b->total) }}</td>
                    <td><x-pay :value="optional($b->invoice)->payment_status" /></td>
                    <td><x-status :value="$b->status" /></td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">@if ($active || $filters['status']) No bookings match these filters. @else No hotel bookings yet. <a href="{{ route('invoices.create', ['service' => 'hotel']) }}">Create the first one</a>. @endif</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pager', ['p' => $bookings])
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-auto]').forEach(function (el) {
        el.addEventListener('change', function () { document.getElementById('filter-form').submit(); });
    });
    document.querySelectorAll('tr.row').forEach(function (tr) {
        tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });
</script>
@endpush
