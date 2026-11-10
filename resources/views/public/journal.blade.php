@extends('layouts.site')

@php
    $journalDescription = $journal->description
        ?: 'Peer-reviewed research published by '.$journal->title.' at Akwa Ibom State University.';

    $journalStructuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'Periodical',
        'name' => $journal->title,
        'alternateName' => $journal->short_title,
        'issn' => $journal->issn,
        'description' => $journal->description,
        'url' => route('journals.show', $journal),
        'publisher' => [
            '@type' => 'CollegeOrUniversity',
            'name' => 'Akwa Ibom State University',
        ],
    ];
@endphp

@section('title', $journal->title.' | Aksujet')
@section('description', Str::limit(strip_tags($journalDescription), 155))
@section('canonical', route('journals.show', $journal))

@push('structured-data')
    <script type="application/ld+json" nonce="{{ $cspNonce }}">@json($journalStructuredData)</script>
@endpush

@section('content')
    <section class="hero hero--compact">
        <div class="hero-orbit" aria-hidden="true"></div>

        <div class="page-wrap hero-copy">
            <p class="eyebrow eyebrow--copper">Aksujet journal profile</p>
            <h1 class="article-page-title">{{ $journal->title }}</h1>
            <p class="hero-lede">
                {{ $journal->description ?: 'Peer-reviewed scholarship from Akwa Ibom State University.' }}
            </p>

            <div class="article-meta">
                @if($journal->issn)
                    <span>ISSN {{ $journal->issn }}</span>
                @endif

                @if($journal->eissn)
                    <span>eISSN {{ $journal->eissn }}</span>
                @endif

                <span>Akwa Ibom State University</span>
            </div>
        </div>
    </section>

    <section class="section section--paper">
        <div class="page-wrap reading-layout">
            <div class="reading-main">
                <p class="eyebrow">Journal archive</p>
                <h2 class="section-title">Published articles.</h2>

                <div class="archive-grid">
                    @forelse($articles as $article)
                        @include('public.partials.article-card', ['article' => $article])
                    @empty
                        <div class="empty-state empty-state--wide">
                            <h3>No articles have been published yet.</h3>
                            <p>
                                When the editorial team publishes an issue, its article records
                                will be listed here.
                            </p>
                        </div>
                    @endforelse
                </div>

                @if($articles->hasPages())
                    <nav class="pagination" aria-label="Article pages">
                        {{ $articles->links() }}
                    </nav>
                @endif
            </div>

            <aside class="reading-sidebar">
                <p class="eyebrow">Journal scope</p>
                <h2>About this journal.</h2>
                <p class="panel-copy">
                    {{ $journal->scope ?: $journal->description ?: 'Scope information will be maintained by the editorial office.' }}
                </p>

                @if($journal->contact_email)
                    <a href="mailto:{{ $journal->contact_email }}">Contact the journal office</a>
                @endif

                <h2>Published issues</h2>

                @forelse($issues as $issue)
                    <a href="{{ route('issues.show', $issue) }}">
                        {{ $issue->title ?: 'Volume '.$issue->volume.', Issue '.$issue->issue_number }}
                    </a>
                @empty
                    <p class="panel-copy">No public issues yet.</p>
                @endforelse
            </aside>
        </div>
    </section>
@endsection
