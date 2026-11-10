<x-guest-layout>
@section('title','Confirm your password')
<div class="auth-heading"><p class="eyebrow">Security check</p><h1>Confirm it’s you</h1><p>For your security, re-enter your password before continuing to this protected action.</p></div>
@if($errors->any())<div class="flash flash--error" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.confirm') }}" class="form-stack">@csrf<label class="field"><span>Password</span><input class="field-input" type="password" name="password" autocomplete="current-password" required autofocus></label><button type="submit" class="button button--pine">Confirm password <span aria-hidden="true">↗</span></button></form>
</x-guest-layout>
