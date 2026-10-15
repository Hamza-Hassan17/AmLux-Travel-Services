@extends('layouts.app')
@section('title', 'Cash & bank')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Directory<span class="sep"></span><b>Cash &amp; bank</b></div>
        <h1>Cash &amp; bank accounts</h1>
    </div>
</div>

<div class="settings-grid">
    <section class="panel panel-clip">
        <div class="panel-head"><x-icon name="building-columns" />Accounts (modes of payment)</div>
        <div class="table-wrap">
            <table class="data narrow">
                <thead><tr><th>Name</th><th>Type</th><th class="right">Opening balance</th><th class="right">Balance now</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @foreach ($accounts as $a)
                    <tr>
                        <td><input class="input" form="acc-{{ $a->id }}" name="name" value="{{ $a->name }}" required></td>
                        <td>{{ ucfirst($a->type) }}</td>
                        <td class="right"><input class="input" form="acc-{{ $a->id }}" style="width:130px;text-align:right" type="number" step="0.01" name="opening_balance" value="{{ $a->opening_balance }}"></td>
                        <td class="right strong">{{ number_format($a->balance) }}</td>
                        <td><input type="checkbox" form="acc-{{ $a->id }}" name="is_active" value="1" {{ $a->is_active ? 'checked' : '' }}></td>
                        <td class="right"><button class="btn btn-ghost btn-sm" form="acc-{{ $a->id }}">Save</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @foreach ($accounts as $a)
            <form id="acc-{{ $a->id }}" method="POST" action="{{ route('accounts.update', $a) }}">@csrf @method('PUT')</form>
        @endforeach
        <div class="tbl-note">Receipts add to an account and payments take from it. Cards and payment gateways can be added as accounts too.</div>
    </section>

    <form method="POST" action="{{ route('accounts.store') }}">
        @csrf
        <section class="panel">
            <div class="panel-head"><x-icon name="plus" />Add account</div>
            <div class="panel-body grid">
                <div class="field c12"><label for="name">Name <span class="req">*</span></label><input id="name" name="name" class="input" placeholder="e.g. Meezan Bank" required></div>
                <div class="field c6">
                    <label for="type">Type</label>
                    <select id="type" name="type" class="select">
                        <option value="bank">Bank</option><option value="cash">Cash</option><option value="gateway">Payment gateway</option>
                    </select>
                </div>
                <div class="field c6"><label for="opening_balance">Opening balance</label><input id="opening_balance" type="number" step="0.01" name="opening_balance" class="input" value="0"></div>
            </div>
            <div class="panel-body actions" style="border-top:1px solid var(--line)"><button class="btn btn-red">Add account</button></div>
        </section>
    </form>
</div>
@endsection
