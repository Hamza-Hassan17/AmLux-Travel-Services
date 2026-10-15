@extends('layouts.app')
@section('title', 'Add customer')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('customers.index') }}">Directory</a><span class="sep"></span><a href="{{ route('customers.index') }}">Customers</a><span class="sep"></span><b>New</b></div>
        <h1>Add customer</h1>
    </div>
</div>

<form method="POST" action="{{ route('customers.store') }}">
    @csrf
    <section class="panel" style="max-width:900px">
        <div class="panel-head"><x-icon name="user" />Customer entry</div>
        <div class="panel-body grid">
            <div class="field c6"><label for="name">Full name <span class="req">*</span></label><input id="name" name="name" class="input" value="{{ old('name') }}" required></div>
            <div class="field c6"><label for="phone">Mobile <span class="req">*</span></label><input id="phone" name="phone" class="input" value="{{ old('phone') }}" placeholder="+92 300 1234567" required></div>
            <div class="field c6"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}"></div>
            <div class="field c6"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" class="input" value="{{ old('date_of_birth') }}"></div>
            <div class="field c4"><label for="passport_no">Passport no.</label><input id="passport_no" name="passport_no" class="input upper" value="{{ old('passport_no') }}"></div>
            <div class="field c4"><label for="nationality">Nationality</label><input id="nationality" name="nationality" class="input" value="{{ old('nationality', 'Pakistani') }}"></div>
            <div class="field c4"><label for="country">Country</label><input id="country" name="country" class="input" value="{{ old('country', 'Pakistan') }}"></div>
            <div class="field c4"><label for="tax_no">Tax / NTN no.</label><input id="tax_no" name="tax_no" class="input" value="{{ old('tax_no') }}"></div>
            <div class="field c4"><label for="opening_balance">Opening balance (they owe us)</label><input id="opening_balance" type="number" step="0.01" min="0" name="opening_balance" class="input" value="{{ old('opening_balance', 0) }}"></div>
            <div class="field c12"><label for="address">Address</label><input id="address" name="address" class="input" value="{{ old('address') }}"></div>
        </div>
        <div class="panel-body actions" style="border-top:1px solid var(--line)">
            <button class="btn btn-red">Save customer</button>
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </section>
</form>
@endsection
