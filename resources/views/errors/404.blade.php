@extends('layouts.site')
@section('title','Page not found | Aksujet')
@section('description','The requested Aksujet page is not available.')
@section('robots','noindex,nofollow')
@section('content')<section class="section section--paper"><div class="page-wrap empty-state"><p class="eyebrow">404 · Not in this issue</p><h1 class="section-title">This page could not be found.</h1><p>The link may be outdated, or this work is not publicly published.</p><a class="button button--pine" href="{{ route('home') }}">Return to Aksujet</a></div></section>@endsection
