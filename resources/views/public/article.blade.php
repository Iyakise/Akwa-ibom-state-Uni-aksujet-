@extends('layouts.site')
@section('title', $article->title.' | Aksujet')
@section('description', Str::limit(strip_tags($article->abstract), 155))
@section('canonical', route('articles.show', $article))
@section('og_type', 'article')

@php
  $citationDate = $article->publication_date ?? $article->issue?->published_on ?? $article->published_at;
@endphp

@push('citation')
<meta name="citation_title" content="{{ $article->title }}">
<meta name="citation_journal_title" content="{{ $article->journal->title }}">
<meta name="citation_publication_date" content="{{ $citationDate?->format('Y/m/d') }}">
<meta name="citation_language" content="{{ $article->language ?: 'en' }}">
<meta name="citation_public_url" content="{{ route('articles.show', $article) }}">
<meta name="citation_pdf_url" content="{{ route('articles.pdf', $article) }}">
<meta name="citation_abstract" content="{{ Str::limit(strip_tags($article->abstract), 1000) }}">
@foreach($article->authors as $author)
<meta name="citation_author" content="{{ trim($author->given_name.' '.$author->family_name) }}">
@if($author->affiliation)<meta name="citation_author_institution" content="{{ $author->affiliation }}">@endif
@endforeach
@if($article->doi)<meta name="citation_doi" content="{{ $article->doi }}">@endif
@if($article->issue)<meta name="citation_volume" content="{{ $article->issue->volume }}"><meta name="citation_issue" content="{{ $article->issue->issue_number }}">@endif
@if($article->page_range)<meta name="citation_firstpage" content="{{ preg_split('/[–-]/u', $article->page_range)[0] }}">@endif
@if($article->keywords)<meta name="citation_keywords" content="{{ implode('; ', $article->keywords) }}">@endif
@endpush

@php
  $articleStructuredData = [
  '@context' => 'https://schema.org',
  '@type' => 'ScholarlyArticle',
  'headline' => $article->title,
  'abstract' => $article->abstract,
  'datePublished' => $citationDate?->toAtomString(),
  'dateModified' => $article->updated_at?->toAtomString(),
  'inLanguage' => $article->language ?: 'en',
  'url' => route('articles.show', $article),
  'identifier' => $article->doi ? ['@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => $article->doi] : null,
  'isPartOf' => ['@type' => 'Periodical', 'name' => $article->journal->title, 'issn' => $article->journal->issn],
  'author' => $article->authors->map(fn ($author) => ['@type' => 'Person', 'name' => trim($author->given_name.' '.$author->family_name), 'affiliation' => $author->affiliation ? ['@type' => 'Organization', 'name' => $author->affiliation] : null])->values(),
  'publisher' => ['@type' => 'CollegeOrUniversity', 'name' => 'Akwa Ibom State University'],
  'sameAs' => route('articles.pdf', $article),
  ];
@endphp
@push('structured-data')
<script type="application/ld+json" nonce="{{ $cspNonce }}">@json($articleStructuredData)</script>
@endpush

@section('content')
<article>
  <header class="hero hero--compact">
    <div class="hero-orbit" aria-hidden="true"></div>
    <div class="page-wrap hero-copy">
      <p class="eyebrow eyebrow--copper"><a href="{{ route('journals.show', $article->journal) }}">{{ $article->journal->title }}</a>@if($article->issue) · <a href="{{ route('issues.show', $article->issue) }}">Vol. {{ $article->issue->volume }}, Issue {{ $article->issue->issue_number }}</a>@endif</p>
      <h1 class="article-page-title">{{ $article->title }}</h1>
      <div class="article-authors">@foreach($article->authors as $author)<span>{{ trim($author->given_name.' '.$author->family_name) }}@if($author->affiliation) <small>({{ $author->affiliation }})</small>@endif</span>@endforeach</div>
      <div class="article-meta"><span>Published {{ $citationDate?->format('F j, Y') }}</span>@if($article->page_range)<span>Pages {{ $article->page_range }}</span>@endif @if($article->doi)<span>DOI {{ $article->doi }}</span>@endif</div>
    </div>
  </header>
  <section class="section section--paper"><div class="page-wrap reading-layout">
    <div class="reading-main">
      <p class="eyebrow">Abstract</p><div class="article-abstract-box">{{ $article->abstract }}</div>
      @if($article->keywords)<p class="eyebrow">Keywords</p><div class="keyword-row">@foreach($article->keywords as $keyword)<span class="keyword-pill">{{ $keyword }}</span>@endforeach</div>@endif
      @if($article->publicFiles->isNotEmpty())<div class="article-download-panel"><div><h2>Read the full article</h2><p>Open the final, editor-approved full text as a PDF.</p></div><a class="button button--pine" href="{{ route('articles.pdf', $article) }}">Download PDF <span aria-hidden="true">↗</span></a></div>@endif
      @if($article->license)<p class="panel-copy">License: {{ $article->license }}</p>@endif
    </div>
    <aside class="reading-sidebar"><p class="eyebrow">Article details</p><h2>Cite this work.</h2><p class="panel-copy">{{ $article->title }}. <em>{{ $article->journal->title }}</em>@if($article->issue), {{ $article->issue->volume }}({{ $article->issue->issue_number }})@endif @if($article->page_range), {{ $article->page_range }}@endif @if($citationDate), {{ $citationDate->format('Y') }}@endif.</p>@if($article->doi)<p class="panel-copy">DOI: {{ $article->doi }}</p>@endif<a href="{{ route('articles.pdf', $article) }}">Open full text PDF</a><a href="{{ route('journals.show', $article->journal) }}">Journal profile</a></aside>
  </div></section>
</article>
@endsection
