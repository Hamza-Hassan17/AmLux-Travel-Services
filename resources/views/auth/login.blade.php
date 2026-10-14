<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · AmLux Travel Services</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/amlux.css') }}?v={{ filemtime(public_path('css/amlux.css')) }}">
</head>
<body class="auth-body">
<main class="auth">
    <div class="auth-card">
        <img class="auth-logo" src="{{ asset('img/logo.webp') }}" alt="AmLux Travel Services" width="190" height="107">
        <h1>Welcome back</h1>
        <p class="auth-sub">Sign in to the AmLux back office.</p>

        <form method="POST" action="{{ route('login.attempt') }}" class="auth-form">
            @csrf
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" class="input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@amluxtravels.com">
                @error('email')<span class="field-err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="pw">
                    <input id="password" type="password" name="password" class="input" required autocomplete="current-password" placeholder="••••••••">
                    <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Show password"><x-icon name="eye" /></button>
                </div>
                @error('password')<span class="field-err">{{ $message }}</span>@enderror
            </div>
            <label class="check"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> Keep me signed in on this computer</label>
            <button class="btn btn-navy btn-lg auth-btn">Sign in</button>
        </form>
    </div>
    <p class="auth-foot">More Than Just Tickets &nbsp;•&nbsp; <span>We create journeys</span></p>
</main>
<script>
    document.getElementById('pw-toggle').addEventListener('click', function () {
        var f = document.getElementById('password');
        f.type = f.type === 'password' ? 'text' : 'password';
    });
</script>
</body>
</html>
