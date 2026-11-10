@php($authors = $article->authors->map(fn ($author) => trim($author->given_name.' '.$author->family_name))->filter())
@php($citationDate = $article->publication_date ?? $article->issue?->published_on ?? $article->published_at)
<article class="archive-card searchable" data-kind="article" data-search="{{ mb_strtolower($article->title.' '.$article->journal->title.' '.implode(' ', $article->keywords ?? []).' '.$authors->implode(' ')) }}">
  <span class="eyebrow">{{ $article->journal->short_title ?? $article->journal->title }}@if($article->issue) · Vol. {{ $article->issue->volume }}, Issue {{ $article->issue->issue_number }}@endif</span>
  <h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3>
  <p>{{ $authors->implode(', ') ?: 'Aksujet author' }}@if($citationDate) · {{ $citationDate->format('Y') }}@endif</p>
  <p>{{ Str::limit(strip_tags($article->abstract), 165) }}</p>
  <a class="text-link" href="{{ route('articles.show', $article) }}">Read article <span aria-hidden="true">→</span></a>
</article>
