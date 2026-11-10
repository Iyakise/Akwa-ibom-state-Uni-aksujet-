<!doctype html>
<html lang="en-NG">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#0c342b"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Aksujet Workspace</title>
  <link rel="icon" href="{{ asset('assets/aksu-crest.png') }}" type="image/png"><link rel="stylesheet" href="{{ asset('styles.css') }}"><script defer src="{{ asset('script.js') }}"></script>
</head>
<body class="workspace-body">
  <a class="skip-link" href="#workspace-main">Skip to workspace</a>
  <header class="workspace-header"><div class="page-wrap workspace-header-inner">
    <a class="brand-lockup" href="{{ route('home') }}"><img class="brand-crest" src="{{ asset('assets/aksu-crest.png') }}" alt=""><span class="brand-text"><span class="brand-name">Aksujet</span><span class="brand-subtitle">Editorial workspace</span></span></a>
    <nav class="workspace-nav primary-navigation" id="workspace-navigation" aria-label="Workspace navigation">
      <a href="{{ route('home') }}">Public site</a>
      @if(auth()->user()->hasAnyRole(['administrator','editor']))
        <a href="{{ route('editor.dashboard') }}">Editorial desk</a><a href="{{ route('editor.archive.index') }}">Article archive</a>
      @endif
      @if(auth()->user()->hasRole('administrator'))<a href="{{ route('editor.team.index') }}">Team access</a>@endif
      @if(auth()->user()->hasRole('reviewer'))<a href="{{ route('reviewer.dashboard') }}">Review desk</a>@endif
      @if(auth()->user()->hasAnyRole(['author','editor','administrator']))<a href="{{ route('author.submissions.index') }}">Submissions</a>@endif
      <a href="{{ route('profile.edit') }}">Account</a>
      <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-link">Sign out</button></form>
    </nav>
    <button class="menu-toggle workspace-menu-toggle" type="button" aria-label="Open workspace navigation" aria-expanded="false" aria-controls="workspace-navigation"><span></span><span></span></button>
  </div></header>
  <main id="workspace-main" class="workspace-main"><div class="page-wrap">
    @if(isset($header))<div class="workspace-page-heading">{{ $header }}</div>@endif
    @if(session('status'))<div class="flash flash--success" role="status">{{ session('status') }}</div>@endif
    {!! $slot !!}
  </div></main>
  <footer class="workspace-footer"><div class="page-wrap"><span>© {{ now()->year }} Aksujet · Private workspace</span><a href="mailto:aksujet@aksu.edu.ng">Editorial office</a></div></footer>
</body>
</html>
