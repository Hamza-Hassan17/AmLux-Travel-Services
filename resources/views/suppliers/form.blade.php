@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit ' . $supplier->name : 'Add supplier')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('suppliers.index') }}">Directory</a><span class="sep"></span><a href="{{ route('suppliers.index') }}">Suppliers</a><span class="sep"></span><b>{{ $supplier->exists ? 'Edit' : 'New' }}</b></div>
        <h1>{{ $supplier->exists ? 'Edit supplier' : 'Add supplier' }}</h1>
    </div>
</div>

<form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
    @csrf
    @if ($supplier->exists) @method('PUT') @endif
    <section class="panel" style="max-width:860px">
        <div class="panel-head"><x-icon name="building" />Supplier</div>
        <div class="panel-body grid">
            <div class="field c6"><label for="name">Name <span class="req">*</span></label><input id="name" name="name" class="input" value="{{ old('name', $supplier->name) }}" required></div>
            <div class="field c6"><label for="phone">Phone</label><input id="phone" name="phone" class="input" value="{{ old('phone', $supplier->phone) }}"></div>
            <div class="field c6"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $supplier->email) }}"></div>
            <div class="field c6"><label for="opening_balance">Opening balance (we owe them)</label><input id="opening_balance" type="number" step="0.01" name="opening_balance" class="input" value="{{ old('opening_balance', $supplier->opening_balance ?? 0) }}"></div>
            <div class="field c12"><label for="address">Address</label><input id="address" name="address" class="input" value="{{ old('address', $supplier->address) }}"></div>
            <div class="field c12"><label for="notes">Notes</label><textarea id="notes" name="notes" class="textarea" rows="2">{{ old('notes', $supplier->notes) }}</textarea></div>
        </div>
        <div class="panel-body actions" style="border-top:1px solid var(--line)">
            <button class="btn btn-red">Save supplier</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </section>
</form>
@endsection
