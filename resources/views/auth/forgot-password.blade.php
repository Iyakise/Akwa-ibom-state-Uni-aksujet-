<x-guest-layout>
@section('title','Reset password')
<div class="auth-heading"><p class="eyebrow">Account recovery</p><h1>Reset your password</h1><p>Enter the email used for your Aksujet account. If it is registered, the reset link will be sent through the configured mail service.</p></div>
@if(session('status'))<div class="flash flash--success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="flash flash--error" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.email') }}" class="form-stack">@csrf<label class="field"><span>Email address</span><input class="field-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label><div class="auth-actions"><button type="submit" class="button button--pine">Send reset link <span aria-hidden="true">↗</span></button></div></form>
<p class="auth-switch"><a href="{{ route('login') }}">Return to sign in</a></p>
</x-guest-layout>
