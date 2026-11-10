<x-guest-layout>
@section('title','Sign in')
<div class="auth-heading"><p class="eyebrow">Editorial workspace</p><h1>Sign in to Aksujet</h1><p>Use your verified account to manage submissions, reviews or publications.</p></div>
<x-auth-session-status class="flash flash--success" :status="session('status')" />
@if($errors->any())<div class="flash flash--error" role="alert">Please check your email and password and try again.</div>@endif
<form method="POST" action="{{ route('login') }}" class="form-stack">@csrf
<label class="field"><span>Email address</span><input class="field-input" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
<label class="field"><span>Password</span><input class="field-input" type="password" name="password" autocomplete="current-password" required></label>
<label class="check-row"><input type="checkbox" name="remember"><span>Keep me signed in on this device</span></label>
<div class="auth-actions"><button class="button button--pine" type="submit">Sign in <span aria-hidden="true">↗</span></button>@if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-link">Forgot password?</a>@endif</div>
</form>
<p class="auth-switch">New to Aksujet? <a href="{{ route('register') }}">Create an author account</a></p>
</x-guest-layout>
