<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialDecision extends Model
{
    protected $fillable = ['manuscript_id', 'review_round_id', 'editor_id', 'decision', 'author_visible_reason', 'decision_letter', 'decided_at'];
    protected function casts(): array { return ['decided_at' => 'datetime']; }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function round(): BelongsTo { return $this->belongsTo(ReviewRound::class, 'review_round_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'editor_id'); }
}
