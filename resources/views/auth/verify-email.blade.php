<x-guest-layout>
@section('title','Verify email')
<div class="auth-heading"><p class="eyebrow">One quick step</p><h1>Verify your email</h1><p>We sent a verification link to your account email. Open that link to activate your workspace access.</p></div>
@if(session('status') === 'verification-link-sent')<div class="flash flash--success" role="status">A fresh verification link has been sent.</div>@endif
<form method="POST" action="{{ route('verification.send') }}" class="form-stack">@csrf<button class="button button--pine" type="submit">Resend verification email</button></form>
<form method="POST" action="{{ route('logout') }}" class="auth-secondary-form">@csrf<button type="submit" class="text-link">Sign out</button></form>
</x-guest-layout>
