<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleVersion extends Model
{
    protected $fillable = ['article_id', 'article_file_id', 'replaced_by', 'version_number', 'change_note', 'published_at'];
    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function article(): BelongsTo { return $this->belongsTo(Article::class); }
    public function file(): BelongsTo { return $this->belongsTo(ArticleFile::class, 'article_file_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'replaced_by'); }
}
