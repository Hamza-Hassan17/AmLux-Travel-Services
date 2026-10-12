<div class="kpis">
    <div class="kpi">
        <div class="kpi-label">Bookings this month</div>
        <div class="kpi-val">
            <b>{{ number_format($kpis['month']) }}</b>
            @if (! is_null($kpis['trend']))
                <small class="{{ $kpis['trend'] >= 0 ? 'up' : 'down' }}">{{ $kpis['trend'] >= 0 ? '+' : '' }}{{ $kpis['trend'] }}% vs {{ $kpis['lastMonth'] }}</small>
            @endif
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Revenue this month</div>
        <div class="kpi-val"><span class="unit">PKR</span><b>{{ $kpis['revenue'] }}</b></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Awaiting ticketing</div>
        <div class="kpi-val"><b class="warn">{{ $kpis['pending'] }}</b>@if ($kpis['pendingSoon'])<small>{{ $kpis['pendingSoon'] }} depart in 3 days</small>@endif</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Departing in 7 days</div>
        <div class="kpi-val"><b>{{ $kpis['upcoming'] }}</b></div>
    </div>
</div>
