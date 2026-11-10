<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['review_assignment_id', 'recommendation', 'comments_to_author', 'confidential_comments_to_editor', 'ratings', 'submitted_at'];
    protected function casts(): array { return ['ratings' => 'array', 'submitted_at' => 'datetime']; }
    public function assignment(): BelongsTo { return $this->belongsTo(ReviewAssignment::class, 'review_assignment_id'); }
}
