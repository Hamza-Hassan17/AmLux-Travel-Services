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
