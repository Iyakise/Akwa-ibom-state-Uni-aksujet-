<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAuthor extends Model
{
    protected $fillable = ['article_id', 'position', 'given_name', 'family_name', 'affiliation', 'orcid', 'email', 'is_corresponding'];
    protected function casts(): array { return ['is_corresponding' => 'boolean']; }
    public function article(): BelongsTo { return $this->belongsTo(Article::class); }
}
