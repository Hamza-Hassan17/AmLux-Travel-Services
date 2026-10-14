@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('dashboard') }}">Dashboard</a><span class="sep"></span><b>Settings</b></div>
        <h1>Settings</h1>
    </div>
</div>

<div class="settings-grid">
    <form method="POST" action="{{ route('settings.agency') }}" class="stack">
        @csrf @method('PUT')
        <section class="panel">
            <div class="panel-head"><x-icon name="building" />Agency details</div>
            <div class="panel-body grid">
                <div class="field c12"><span class="sub">These details appear on every new invoice, e-ticket and voucher you generate.</span></div>
                <div class="field c6"><label for="name">Agency name <span class="req">*</span></label><input id="name" name="name" class="input" value="{{ old('name', $agency['name']) }}" required></div>
                <div class="field c6"><label for="tagline">Tagline</label><input id="tagline" name="tagline" class="input" value="{{ old('tagline', $agency['tagline']) }}"></div>
                <div class="field c12"><label for="head_office">Head office address</label><input id="head_office" name="head_office" class="input" value="{{ old('head_office', $agency['head_office']) }}"></div>
                <div class="field c6"><label for="helpline">Direct helpline</label><input id="helpline" name="helpline" class="input" value="{{ old('helpline', $agency['helpline']) }}"></div>
                <div class="field c6"><label for="mobile">Mobile / WhatsApp</label><input id="mobile" name="mobile" class="input" value="{{ old('mobile', $agency['mobile']) }}"></div>
                <div class="field c6"><label for="email">Official email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $agency['email']) }}"></div>
                <div class="field c6"><label for="reservations_email">Reservations email</label><input id="reservations_email" type="email" name="reservations_email" class="input" value="{{ old('reservations_email', $agency['reservations_email']) }}"></div>
                <div class="field c6"><label for="website">Website</label><input id="website" name="website" class="input" value="{{ old('website', $agency['website']) }}"></div>
                <div class="field c6"><label for="default_currency">Default currency <span class="req">*</span></label><input id="default_currency" name="default_currency" maxlength="3" class="input upper" value="{{ old('default_currency', $agency['default_currency']) }}" required></div>
            </div>
            <div class="panel-body actions" style="border-top:1px solid var(--line)">
                <button class="btn btn-red">Save agency details</button>
            </div>
        </section>
    </form>

    <div class="stack">
        <form method="POST" action="{{ route('settings.profile') }}">
            @csrf @method('PUT')
            <section class="panel">
                <div class="panel-head"><x-icon name="user" />My account</div>
                <div class="panel-body grid">
                    <div class="field c12"><label for="p_name">Name</label><input id="p_name" name="name" class="input" value="{{ old('name', $user->name) }}" required></div>
                    <div class="field c12"><label for="p_email">Login email</label><input id="p_email" type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required></div>
                </div>
                <div class="panel-body actions" style="border-top:1px solid var(--line)">
                    <button class="btn btn-navy">Update profile</button>
                </div>
            </section>
        </form>

        <form method="POST" action="{{ route('settings.password') }}">
            @csrf @method('PUT')
            <section class="panel">
                <div class="panel-head"><x-icon name="key" />Change password</div>
                <div class="panel-body grid">
                    <div class="field c12"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="input" autocomplete="current-password" required></div>
                    <div class="field c12"><label for="password">New password</label><input id="password" type="password" name="password" class="input" autocomplete="new-password" minlength="8" required><span class="hint">At least 8 characters.</span></div>
                    <div class="field c12"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password" required></div>
                </div>
                <div class="panel-body actions" style="border-top:1px solid var(--line)">
                    <button class="btn btn-navy">Change password</button>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
