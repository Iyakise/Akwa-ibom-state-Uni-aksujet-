<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use SoftDeletes;

    protected $fillable = ['journal_id', 'volume', 'issue_number', 'publication_year', 'title', 'slug', 'description', 'cover_path', 'published_on', 'status'];

    protected function casts(): array { return ['published_on' => 'date']; }
    public function getRouteKeyName(): string { return 'slug'; }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function articles(): HasMany { return $this->hasMany(Article::class); }
}
