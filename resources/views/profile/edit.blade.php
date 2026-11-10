<x-app-layout>
<x-slot name="header"><p class="eyebrow">Account settings</p><h1 class="workspace-title">Research profile</h1><p class="workspace-subtitle">Maintain the contact and affiliation details associated with your account.</p></x-slot>
@if(session('status') === 'profile-updated')<div class="flash flash--success" role="status">Your profile has been updated.</div>@endif
@if(session('status') === 'account-deleted')<div class="flash flash--success" role="status">Your account has been closed.</div>@endif
@if($errors->any())<div class="flash flash--error" role="alert">{{ $errors->first() }}</div>@endif
<section class="workspace-panel"><form method="POST" action="{{ route('profile.update') }}" class="workspace-form">@csrf @method('PATCH')
  <label class="field"><span>Full name</span><input class="field-input" name="name" value="{{ old('name',$user->name) }}" maxlength="255" required></label>
  <label class="field"><span>Email address</span><input class="field-input" type="email" name="email" value="{{ old('email',$user->email) }}" maxlength="255" required>@if(!$user->hasVerifiedEmail())<small class="field-error">Email not verified. Verify before using private publishing features.</small>@endif</label>
  <label class="field"><span>Institution / affiliation <small>Optional</small></span><input class="field-input" name="affiliation" value="{{ old('affiliation',$user->affiliation) }}" maxlength="255"></label>
  <label class="field"><span>ORCID <small>Optional</small></span><input class="field-input" name="orcid" value="{{ old('orcid',$user->orcid) }}" placeholder="0000-0000-0000-0000" maxlength="19"></label>
  <button class="button button--pine" type="submit">Save profile</button>
</form></section>
<section class="workspace-panel workspace-panel--spaced"><p class="eyebrow">Password</p><h2>Update your password</h2><form method="POST" action="{{ route('password.update') }}" class="workspace-form">@csrf @method('PUT')
  <label class="field"><span>Current password</span><input class="field-input" type="password" name="current_password" autocomplete="current-password" required></label>
  <label class="field"><span>New password</span><input class="field-input" type="password" name="password" autocomplete="new-password" required></label>
  <label class="field"><span>Confirm new password</span><input class="field-input" type="password" name="password_confirmation" autocomplete="new-password" required></label>
  <button class="button button--pine" type="submit">Update password</button>
</form></section>
@if(!$user->isEditorialAllowlisted() && !$user->hasRole('administrator'))
<section class="workspace-panel workspace-panel--spaced"><p class="eyebrow">Account closure</p><h2>Close this account</h2><p class="panel-copy">This permanently removes your account. Keep any submission references you still need before continuing.</p><form method="POST" action="{{ route('profile.destroy') }}" class="workspace-form" data-confirm="Permanently close this account?">@csrf @method('DELETE')
  <label class="field"><span>Confirm with your password</span><input class="field-input" type="password" name="password" autocomplete="current-password" required></label>
  <button class="button button--pine" type="submit">Close account</button>
</form></section>
@endif
</x-app-layout>
