@extends('layouts.app')
@section('title', 'Add customer')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('customers.index') }}">Directory</a> &nbsp;/&nbsp; <a href="{{ route('customers.index') }}">Customers</a> &nbsp;/&nbsp; New</div>
        <h1>Add customer</h1>
    </div>
</div>

<form method="POST" action="{{ route('customers.store') }}">
    @csrf
    <section class="panel" style="max-width:860px">
        <div class="panel-head">Customer entry</div>
        <div class="panel-body grid">
            <div class="field c6"><label for="name">Full name <span class="req">*</span></label><input id="name" name="name" class="input" value="{{ old('name') }}" required></div>
            <div class="field c6"><label for="phone">Phone <span class="req">*</span></label><input id="phone" name="phone" class="input" value="{{ old('phone') }}" placeholder="+92 300 1234567" required></div>
            <div class="field c6"><label for="email">Email <span class="req">*</span></label><input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required></div>
            <div class="field c6"><label for="passport_no">Passport no. <span class="req">*</span></label><input id="passport_no" name="passport_no" class="input mono upper" value="{{ old('passport_no') }}" required></div>
            <div class="field c6"><label for="nationality">Nationality <span class="req">*</span></label><input id="nationality" name="nationality" class="input" value="{{ old('nationality', 'Pakistani') }}" required></div>
            <div class="field c6"><label for="address">Address</label><input id="address" name="address" class="input" value="{{ old('address') }}"></div>
        </div>
        <div class="panel-body actions" style="border-top:1px solid var(--line)">
            <button class="btn btn-red">Save &amp; add flight</button>
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </section>
</form>
@endsection
