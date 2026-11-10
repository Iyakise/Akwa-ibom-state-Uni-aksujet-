<x-guest-layout>
@section('title','Choose a new password')
<div class="auth-heading"><p class="eyebrow">Account recovery</p><h1>Choose a new password</h1><p>Create a strong password for your Aksujet account.</p></div>
@if($errors->any())<div class="flash flash--error" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.store') }}" class="form-stack">@csrf<input type="hidden" name="token" value="{{ $request->route('token') }}"><label class="field"><span>Email address</span><input class="field-input" type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required autofocus></label><label class="field"><span>New password</span><input class="field-input" type="password" name="password" autocomplete="new-password" required></label><label class="field"><span>Confirm new password</span><input class="field-input" type="password" name="password_confirmation" autocomplete="new-password" required></label><button type="submit" class="button button--pine">Update password <span aria-hidden="true">↗</span></button></form>
</x-guest-layout>
