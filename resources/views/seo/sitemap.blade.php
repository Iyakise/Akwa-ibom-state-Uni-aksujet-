<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ route('home') }}</loc><lastmod>{{ now()->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>
  @foreach($journals as $journal)<url><loc>{{ route('journals.show',$journal) }}</loc><lastmod>{{ $journal->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>@endforeach
  @foreach($issues as $issue)<url><loc>{{ route('issues.show',$issue) }}</loc><lastmod>{{ $issue->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>@endforeach
  @foreach($articles as $article)<url><loc>{{ route('articles.show',$article) }}</loc><lastmod>{{ $article->updated_at?->toAtomString() ?? $article->published_at->toAtomString() }}</lastmod><changefreq>yearly</changefreq><priority>0.8</priority></url>@endforeach
</urlset>
