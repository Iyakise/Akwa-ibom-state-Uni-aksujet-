<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleFile extends Model
{
    protected $fillable = ['article_id', 'kind', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'is_public'];
    protected function casts(): array { return ['is_public' => 'boolean']; }
    public function article(): BelongsTo { return $this->belongsTo(Article::class); }
}
