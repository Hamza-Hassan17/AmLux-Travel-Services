@extends('layouts.app')
@section('title', $invoice ? 'Edit ' . $invoice->invoice_no : 'New invoice')
@php
    $h = function ($k, $d = null) use ($header) { return old($k, $header[$k] ?? $d); };
    $existingLines = old('lines', $initialLines);
@endphp
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('invoices.index') }}">Transactions</a><span class="sep"></span><a href="{{ route('invoices.index') }}">Invoices</a><span class="sep"></span>{{ $invoice ? $invoice->invoice_no : 'New' }}</div>
        <h1>{{ $invoice ? 'Edit invoice' : 'New invoice' }}</h1>
    </div>
</div>

<form method="POST" action="{{ $invoice ? route('invoices.update', $invoice) : route('invoices.store') }}" id="invoice-form" novalidate>
    @csrf
    @if ($invoice) @method('PUT') @endif

    <div class="form-layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><x-icon name="file-invoice" />Invoice details</div>
                <div class="panel-body grid">
                    <div class="field c3"><label>Invoice no.</label><input class="input" value="{{ $invoice ? $invoice->invoice_no : 'Auto-generated' }}" disabled></div>
                    <div class="field c3"><label for="issued_on">Invoice date <span class="req">*</span></label><input id="issued_on" type="date" name="issued_on" class="input" value="{{ $h('issued_on', now()->toDateString()) }}" required></div>
                    <div class="field c3"><label for="due_date">Due date</label><input id="due_date" type="date" name="due_date" class="input" value="{{ $h('due_date') }}"></div>
                    <div class="field c3">
                        <label for="bill_to_type">Bill to <span class="req">*</span></label>
                        <select id="bill_to_type" name="bill_to_type" class="select">
                            <option value="customer" {{ $h('bill_to_type', 'customer') === 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="walk_in" {{ $h('bill_to_type') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                        </select>
                    </div>

                    <div class="field c6" data-show="customer">
                        <label for="customer_id">Customer <span class="req">*</span></label>
                        <select id="customer_id" name="customer_id" class="select">
                            <option value="">Select customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" {{ (string) $h('customer_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}{{ $c->phone ? ' · ' . $c->phone : '' }}</option>
                            @endforeach
                        </select>
                        <a class="hint" href="{{ route('customers.create') }}">+ New customer</a>
                    </div>
                    <div class="field c6" data-show="walk_in"><label for="walk_in_name">Walk-in name <span class="req">*</span></label><input id="walk_in_name" name="walk_in_name" class="input" value="{{ $h('walk_in_name') }}"></div>
                    <div class="field c3" data-show="walk_in"><label for="walk_in_mobile">Mobile</label><input id="walk_in_mobile" name="walk_in_mobile" class="input" value="{{ $h('walk_in_mobile') }}"></div>

                    <div class="field c4">
                        <label for="agent_id">Commission agent</label>
                        <select id="agent_id" name="agent_id" class="select">
                            <option value="">None</option>
                            @foreach ($agents as $a)
                                <option value="{{ $a->id }}" {{ (string) $h('agent_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field c3" data-show-agent>
                        <label for="agent_commission_mode">Commission</label>
                        <select id="agent_commission_mode" name="agent_commission_mode" class="select">
                            <option value="fixed" {{ $h('agent_commission_mode', 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                            <option value="percent" {{ $h('agent_commission_mode') === 'percent' ? 'selected' : '' }}>% of profit</option>
                        </select>
                    </div>
                    <div class="field c3" data-show-agent><label for="agent_commission_value">Value</label><input id="agent_commission_value" type="number" step="0.01" min="0" name="agent_commission_value" class="input" value="{{ $h('agent_commission_value', 0) }}"></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="suitcase" />Services</div>
                <div class="panel-body">
                    <div id="lines"></div>
                    @error('lines')<div class="field-err" style="margin-top:8px">{{ $message }}</div>@enderror
                    <div class="add-line">
                        <button type="button" class="btn btn-ghost" data-add="flight"><x-icon name="plane" />Add flight ticket</button>
                        <button type="button" class="btn btn-ghost" data-add="hotel"><x-icon name="hotel" />Add hotel booking</button>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><x-icon name="file-lines" />Notes</div>
                <div class="panel-body">
                    <div class="field"><label for="notes">Note printed on the invoice</label><textarea id="notes" name="notes" class="textarea" rows="2">{{ $h('notes') }}</textarea></div>
                </div>
            </section>

            @unless ($invoice)
                <section class="panel">
                    <div class="panel-head"><x-icon name="wallet" />Payment received now <span class="muted" style="text-transform:none;letter-spacing:0;font-weight:400">(optional)</span></div>
                    <div class="panel-body grid">
                        <div class="field c4"><label for="pay_amount">Amount received</label><input id="pay_amount" type="number" step="0.01" min="0" name="pay_amount" class="input" value="{{ old('pay_amount') }}" placeholder="0"></div>
                        <div class="field c4">
                            <label for="pay_account_id">Mode of payment</label>
                            <select id="pay_account_id" name="pay_account_id" class="select">
                                @foreach ($accounts as $ac)
                                    <option value="{{ $ac->id }}" {{ (string) old('pay_account_id') === (string) $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field c4"><label for="pay_reference">Receipt / transaction ref.</label><input id="pay_reference" name="pay_reference" class="input" value="{{ old('pay_reference') }}"></div>
                    </div>
                </section>
            @endunless
        </div>

        <aside class="panel summary">
            <div class="panel-head"><x-icon name="coins" />Invoice summary</div>
            <div class="panel-body">
                <div class="totals-box">
                    <div class="row"><span>Services</span><b id="t-count">0</b></div>
                    <div class="row"><span>Selling price</span><b id="t-selling">0</b></div>
                    <div class="row"><span>Discount</span><b id="t-discount">0</b></div>
                    <div class="row"><span>Service charge</span><b id="t-service">0</b></div>
                    <div class="row"><span>{{ $taxLabel }}</span><b id="t-tax">0</b></div>
                    <div class="row grand"><span>Total</span><b id="t-total">0</b></div>
                    <div class="row"><span>Buying cost</span><b id="t-buying">0</b></div>
                    <div class="row"><span>Profit (excl. tax)</span><b id="t-profit">0</b></div>
                </div>
                <div class="sum-actions">
                    <button class="btn btn-red btn-lg">{{ $invoice ? 'Update invoice' : 'Create invoice' }}</button>
                    <a href="{{ $invoice ? route('invoices.show', $invoice) : route('invoices.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var SUPPLIERS = @json($suppliers);
    var TAX_RATE = {{ $taxRate }};
    var TAX_LABEL = @json($taxLabel);
    var INITIAL = @json(array_values($existingLines));
    var START = @json($startService);
    var NEW = {{ $invoice ? 'false' : 'true' }};
    var ERRORS = @json($errors->keys());
    var form = document.getElementById('invoice-form');
    var box = document.getElementById('lines');
    var counter = 0;

    function esc(v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }
    function num(v) { var n = parseFloat(v); return isFinite(n) ? n : 0; }
    function fmt(n) { return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }); }

    function input(i, f, d, o) {
        o = o || {};
        return '<div class="field ' + (o.c || 'c3') + '"><label>' + o.l + (o.r ? ' <span class="req">*</span>' : '') + '</label>' +
            '<input class="input ' + (o.cls || '') + '" data-f="' + f + '" name="lines[' + i + '][' + f + ']" type="' + (o.t || 'text') + '"' +
            (o.step ? ' step="' + o.step + '"' : '') + (o.min != null ? ' min="' + o.min + '"' : '') + (o.ph ? ' placeholder="' + esc(o.ph) + '"' : '') +
            ' value="' + esc(d[f]) + '"></div>';
    }
    function select(i, f, d, o) {
        var opts = o.options.map(function (p) {
            return '<option value="' + esc(p[0]) + '"' + (String(d[f] == null ? o.def : d[f]) === String(p[0]) ? ' selected' : '') + '>' + esc(p[1]) + '</option>';
        }).join('');
        return '<div class="field ' + (o.c || 'c3') + '"><label>' + o.l + (o.r ? ' <span class="req">*</span>' : '') + '</label>' +
            '<select class="select" data-f="' + f + '" name="lines[' + i + '][' + f + ']">' + opts + '</select></div>';
    }
    function supplierSelect(i, d) {
        var opts = [['', 'Select supplier']].concat(SUPPLIERS.map(function (s) { return [s.id, s.name]; }));
        return select(i, 'supplier_id', d, { l: 'Supplier', c: 'c4', options: opts, def: '' });
    }

    function pricing(i, d, type) {
        var b = d.breakdown || {};
        var bd = '';
        if (type === 'flight') {
            var fields = [['basic_fare', 'Basic fare'], ['taxes', 'Taxes'], ['plb_pct', 'PLB commission %'], ['iata_pct', 'IATA commission %'],
                ['iata_plb_tax_pct', 'IATA/PLB tax %'], ['tcs_pct', 'TCS %'], ['supplier_fee', 'Supplier S.fee'], ['ssf_tax_pct', 'Tax on SSF %'], ['other_tax', 'Other tax amount']];
            bd = '<details class="more"><summary>Fare breakdown (calculates the buying price)</summary><div class="line-grid">' +
                fields.map(function (p) {
                    return '<div class="field c4"><label>' + p[1] + '</label><input class="input" type="number" step="0.01" min="0" data-b="' + p[0] +
                        '" name="lines[' + i + '][breakdown][' + p[0] + ']" value="' + esc(b[p[0]]) + '"></div>';
                }).join('') +
                '<div class="field c12"><span class="sub">Buying price = basic fare + taxes + other tax + TCS on (fare + taxes) + supplier fee with its tax − PLB/IATA commission + tax on that commission.</span></div></div></details>';
        }
        var mode = d.tax_mode || (TAX_RATE > 0 ? 'inclusive' : 'none');
        var rate = d.tax_rate != null && d.tax_rate !== '' ? d.tax_rate : TAX_RATE;
        return '<div class="line-section">Pricing</div><div class="line-grid">' +
            input(i, 'buying_price', d, { l: 'Buying price', c: 'c3', t: 'number', step: '0.01', min: 0 }) +
            input(i, 'selling_price', d, { l: 'Selling price', c: 'c3', t: 'number', step: '0.01', min: 0, r: true }) +
            input(i, 'discount', d, { l: 'Discount', c: 'c3', t: 'number', step: '0.01', min: 0 }) +
            input(i, 'service_charge', d, { l: 'Service charge', c: 'c3', t: 'number', step: '0.01', min: 0 }) +
            select(i, 'tax_mode', { tax_mode: mode }, { l: TAX_LABEL, c: 'c3', def: 'none', options: [['none', 'No ' + TAX_LABEL], ['inclusive', 'Inclusive in price'], ['exclusive', 'Added on top']] }) +
            input(i, 'tax_rate', { tax_rate: rate }, { l: TAX_LABEL + ' %', c: 'c2', t: 'number', step: '0.01', min: 0 }) +
            '<div class="field c4" style="justify-content:flex-end"><label class="check-inline"><input type="checkbox" data-f="taxable_on_margin" name="lines[' + i + '][taxable_on_margin]" value="1"' +
            (d.taxable_on_margin ? ' checked' : '') + '> Tax on margin only</label></div>' +
            '</div>' + bd +
            '<div class="line-calc"><span>' + TAX_LABEL + '<b data-o="tax">0</b></span><span>Line total<b data-o="total">0</b></span><span>Profit<b data-o="profit">0</b></span></div>';
    }

    function flightBody(i, d) {
        return '<div class="line-grid">' +
            input(i, 'pnr', d, { l: 'PNR', c: 'c3', r: true, cls: 'upper' }) +
            input(i, 'ticket_no', d, { l: 'Ticket no.', c: 'c3' }) +
            input(i, 'airline', d, { l: 'Airline', c: 'c3', r: true }) +
            input(i, 'flight_no', d, { l: 'Flight no.', c: 'c3', r: true }) +
            input(i, 'origin', d, { l: 'From (IATA)', c: 'c2', r: true, cls: 'upper' }) +
            input(i, 'destination', d, { l: 'To (IATA)', c: 'c2', r: true, cls: 'upper' }) +
            input(i, 'departure_at', d, { l: 'Date of travel', c: 'c4', t: 'datetime-local', r: true }) +
            input(i, 'arrival_at', d, { l: 'Arrival', c: 'c4', t: 'datetime-local' }) +
            input(i, 'passenger_name', d, { l: 'Name of passenger', c: 'c5', r: true }) +
            input(i, 'passenger_mobile', d, { l: 'Passenger mobile', c: 'c3' }) +
            supplierSelect(i, d) +
            '</div>' +
            '<details class="more"><summary>More itinerary details</summary><div class="line-grid">' +
            input(i, 'aircraft', d, { l: 'Aircraft', c: 'c4' }) +
            input(i, 'origin_airport', d, { l: 'Departure airport', c: 'c4' }) +
            input(i, 'destination_airport', d, { l: 'Arrival airport', c: 'c4' }) +
            select(i, 'cabin_class', d, { l: 'Class', c: 'c3', def: 'Economy', options: ['Economy', 'Premium Economy', 'Business', 'First'].map(function (x) { return [x, x]; }) }) +
            input(i, 'adults', { adults: d.adults || 1 }, { l: 'Passengers', c: 'c2', t: 'number', min: 1 }) +
            input(i, 'seat', d, { l: 'Seat', c: 'c3' }) +
            input(i, 'baggage', d, { l: 'Baggage', c: 'c4' }) +
            input(i, 'meal', d, { l: 'Meal', c: 'c4' }) +
            select(i, 'status', d, { l: 'Ticket status', c: 'c3', def: 'confirmed', options: [['confirmed', 'Confirmed'], ['pending', 'Pending'], ['cancelled', 'Cancelled']] }) +
            '</div></details>' + pricing(i, d, 'flight');
    }

    function hotelBody(i, d) {
        return '<div class="line-grid">' +
            input(i, 'hotel_name', d, { l: 'Hotel name', c: 'c5', r: true }) +
            input(i, 'city', d, { l: 'City', c: 'c3', r: true }) +
            input(i, 'country', d, { l: 'Country', c: 'c4' }) +
            input(i, 'check_in', d, { l: 'Check-in', c: 'c3', t: 'date', r: true }) +
            input(i, 'check_out', d, { l: 'Check-out', c: 'c3', t: 'date', r: true }) +
            input(i, 'rooms', { rooms: d.rooms || 1 }, { l: 'Rooms', c: 'c2', t: 'number', min: 1 }) +
            input(i, 'adults', { adults: d.adults || 2 }, { l: 'Adults', c: 'c2', t: 'number', min: 1 }) +
            input(i, 'children', { children: d.children || 0 }, { l: 'Children', c: 'c2', t: 'number', min: 0 }) +
            input(i, 'guest_name', d, { l: 'Guest name', c: 'c5', r: true }) +
            input(i, 'guest_mobile', d, { l: 'Guest mobile', c: 'c3' }) +
            supplierSelect(i, d) +
            input(i, 'room_type', d, { l: 'Room type', c: 'c4', ph: 'Deluxe Marina View Room' }) +
            input(i, 'meal_plan', d, { l: 'Meal plan', c: 'c4' }) +
            select(i, 'status', d, { l: 'Reservation status', c: 'c4', def: 'confirmed', options: [['confirmed', 'Confirmed'], ['pending', 'Pending'], ['cancelled', 'Cancelled']] }) +
            '</div>' +
            '<details class="more"><summary>More hotel details</summary><div class="line-grid">' +
            select(i, 'star_rating', d, { l: 'Stars', c: 'c2', def: '5', options: [5, 4, 3, 2, 1].map(function (x) { return [x, x + ' ★']; }) }) +
            input(i, 'address', d, { l: 'Address', c: 'c6' }) +
            input(i, 'booking_ref', d, { l: 'Booking reference', c: 'c4', ph: 'Auto-generated if blank', cls: 'upper' }) +
            input(i, 'confirmation_no', d, { l: 'Hotel confirmation no.', c: 'c4' }) +
            input(i, 'hotel_phone', d, { l: 'Hotel phone', c: 'c4' }) +
            input(i, 'hotel_email', d, { l: 'Hotel email', c: 'c4', t: 'email' }) +
            input(i, 'check_in_time', { check_in_time: d.check_in_time || '15:00' }, { l: 'Check-in from', c: 'c3', t: 'time' }) +
            input(i, 'check_out_time', { check_out_time: d.check_out_time || '12:00' }, { l: 'Check-out by', c: 'c3', t: 'time' }) +
            input(i, 'bed_config', d, { l: 'Bed configuration', c: 'c6' }) +
            input(i, 'special_request', d, { l: 'Special request', c: 'c12' }) +
            '<div class="field c12"><label>Amenities (one per line, shown on the voucher)</label><textarea class="textarea" rows="2" name="lines[' + i + '][amenities]">' + esc(d.amenities) + '</textarea></div>' +
            '</div></details>' + pricing(i, d, 'hotel');
    }

    function addLine(type, data) {
        var i = counter++;
        data = data || {};
        var card = document.createElement('div');
        card.className = 'line-card';
        card.dataset.type = type;
        card.innerHTML =
            '<div class="line-head"><span class="svc-badge">' + (type === 'flight' ? '✈ Flight ticket' : '🏨 Hotel booking') + '</span>' +
            '<span class="line-total" data-o="head-total"></span>' +
            '<button type="button" class="icon-btn" title="Remove" data-remove>✕</button></div>' +
            '<div class="line-body">' +
            '<input type="hidden" name="lines[' + i + '][service_type]" value="' + type + '">' +
            (data.id ? '<input type="hidden" name="lines[' + i + '][id]" value="' + data.id + '">' : '') +
            (type === 'flight' ? flightBody(i, data) : hotelBody(i, data)) + '</div>';
        box.appendChild(card);
        calcCard(card);
        recalc();
        return card;
    }

    function calcCard(card) {
        var g = function (f) { var el = card.querySelector('[data-f="' + f + '"]'); return el ? el.value : ''; };
        var buying = num(g('buying_price'));
        var bdInputs = card.querySelectorAll('[data-b]');
        if (bdInputs.length) {
            var b = {}, any = false;
            bdInputs.forEach(function (el) { b[el.dataset.b] = num(el.value); if (el.value !== '') any = true; });
            if (any) {
                var com = b.basic_fare * (b.plb_pct + b.iata_pct) / 100;
                buying = Math.max(0, b.basic_fare + b.taxes + b.other_tax + (b.basic_fare + b.taxes) * b.tcs_pct / 100 +
                    b.supplier_fee * (1 + b.ssf_tax_pct / 100) - com + com * b.iata_plb_tax_pct / 100);
                buying = Math.round(buying * 100) / 100;
                var bp = card.querySelector('[data-f="buying_price"]');
                bp.value = buying; bp.readOnly = true;
            }
        }
        var selling = num(g('selling_price')), discount = num(g('discount')), service = num(g('service_charge'));
        var mode = g('tax_mode'), rate = mode === 'none' ? 0 : num(g('tax_rate'));
        var onMargin = card.querySelector('[data-f="taxable_on_margin"]').checked;
        var net = Math.max(0, selling - discount + service);
        var base = onMargin ? Math.max(0, net - buying) : net;
        var tax = 0, total = net;
        if (mode === 'inclusive' && rate > 0) { tax = base * rate / (100 + rate); }
        else if (mode === 'exclusive' && rate > 0) { tax = base * rate / 100; total = net + tax; }
        var r = { selling: selling, discount: discount, service: service, tax: tax, total: total, buying: buying, profit: total - tax - buying };
        card._calc = r;
        card.querySelector('[data-o="tax"]').textContent = fmt(tax);
        card.querySelector('[data-o="total"]').textContent = fmt(total);
        card.querySelector('[data-o="profit"]').textContent = fmt(r.profit);
        card.querySelector('[data-o="head-total"]').textContent = 'PKR ' + fmt(total);
    }

    function recalc() {
        var sum = { selling: 0, discount: 0, service: 0, tax: 0, total: 0, buying: 0, profit: 0 };
        var cards = box.querySelectorAll('.line-card');
        cards.forEach(function (c) { var r = c._calc; if (!r) return; Object.keys(sum).forEach(function (k) { sum[k] += r[k]; }); });
        document.getElementById('t-count').textContent = cards.length;
        ['selling', 'discount', 'service', 'tax', 'total', 'buying', 'profit'].forEach(function (k) {
            document.getElementById('t-' + k).textContent = fmt(sum[k]);
        });
    }

    form.addEventListener('input', function (e) {
        var card = e.target.closest('.line-card');
        if (card) { calcCard(card); recalc(); }
    });
    form.addEventListener('change', function (e) {
        var card = e.target.closest('.line-card');
        if (card) { calcCard(card); recalc(); }
    });
    form.addEventListener('click', function (e) {
        var rm = e.target.closest('[data-remove]');
        if (rm && confirm('Remove this service from the invoice?')) { rm.closest('.line-card').remove(); recalc(); }
        var add = e.target.closest('[data-add]');
        if (add) { var c = addLine(add.dataset.add, {}); c.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    });

    // bill-to and commission toggles
    var billTo = document.getElementById('bill_to_type'), agent = document.getElementById('agent_id');
    function toggles() {
        document.querySelectorAll('[data-show]').forEach(function (el) { el.style.display = el.dataset.show === billTo.value ? '' : 'none'; });
        document.querySelectorAll('[data-show-agent]').forEach(function (el) { el.style.display = agent.value ? '' : 'none'; });
    }
    billTo.addEventListener('change', toggles);
    agent.addEventListener('change', toggles);
    toggles();

    if (INITIAL.length) { INITIAL.forEach(function (d) { addLine(d.service_type, d); }); }
    else { addLine(START, {}); }
})();
</script>
@endpush
