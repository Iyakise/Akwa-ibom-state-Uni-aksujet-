<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewAssignment extends Model
{
    protected $fillable = ['review_round_id', 'reviewer_id', 'reviewer_email', 'invitation_token_hash', 'status', 'invited_at', 'accepted_at', 'due_at', 'completed_at'];
    protected function casts(): array { return ['invited_at' => 'datetime', 'accepted_at' => 'datetime', 'due_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function round(): BelongsTo { return $this->belongsTo(ReviewRound::class, 'review_round_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function review(): HasOne { return $this->hasOne(Review::class); }
}
