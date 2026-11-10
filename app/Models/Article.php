<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = ['journal_id', 'issue_id', 'manuscript_id', 'slug', 'title', 'abstract', 'keywords', 'doi', 'license', 'language', 'page_range', 'article_number', 'publication_date', 'status', 'published_at', 'withdrawn_at'];

    protected function casts(): array { return ['keywords' => 'array', 'publication_date' => 'date', 'published_at' => 'datetime', 'withdrawn_at' => 'datetime']; }
    public function getRouteKeyName(): string { return 'slug'; }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('journal', fn (Builder $journal) => $journal->where('status', 'active'))
            ->where(fn (Builder $article) => $article->whereNull('issue_id')->orWhereHas('issue', fn (Builder $issue) => $issue->where('status', 'published')));
    }

    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function issue(): BelongsTo { return $this->belongsTo(Issue::class); }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function authors(): HasMany { return $this->hasMany(ArticleAuthor::class)->orderBy('position'); }
    public function files(): HasMany { return $this->hasMany(ArticleFile::class); }
    public function publicFiles(): HasMany { return $this->hasMany(ArticleFile::class)->where('is_public', true); }
    public function versions(): HasMany { return $this->hasMany(ArticleVersion::class)->orderByDesc('version_number'); }
}
