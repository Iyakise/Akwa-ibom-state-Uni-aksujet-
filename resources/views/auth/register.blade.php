<x-guest-layout>
@section('title','Create author account')
<div class="auth-heading"><p class="eyebrow">For researchers</p><h1>Create an author account</h1><p>Register to submit manuscripts and follow editorial progress. New accounts receive author access only.</p></div>
@if($errors->any())<div class="flash flash--error" role="alert">Please correct the highlighted details and try again.</div>@endif
<form method="POST" action="{{ route('register') }}" class="form-stack">@csrf
<label class="field"><span>Full name</span><input class="field-input" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Institutional email</span><input class="field-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Affiliation <small>Optional</small></span><input class="field-input" type="text" name="affiliation" value="{{ old('affiliation') }}" autocomplete="organization" maxlength="255"></label>
<label class="field"><span>ORCID <small>Optional</small></span><input class="field-input" type="text" name="orcid" value="{{ old('orcid') }}" placeholder="0000-0000-0000-0000" maxlength="19">@error('orcid')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Password</span><input class="field-input" type="password" name="password" autocomplete="new-password" required>@error('password')<small class="field-error">{{ $message }}</small>@enderror</label>
<label class="field"><span>Confirm password</span><input class="field-input" type="password" name="password_confirmation" autocomplete="new-password" required></label>
<div class="auth-actions"><button class="button button--pine" type="submit">Create account <span aria-hidden="true">↗</span></button></div>
</form>
<p class="auth-switch">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
</x-guest-layout>
