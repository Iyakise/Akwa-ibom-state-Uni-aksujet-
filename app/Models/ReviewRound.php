<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewRound extends Model
{
    protected $fillable = ['manuscript_id', 'round_number', 'status', 'opened_by', 'opened_at', 'closed_at'];
    protected function casts(): array { return ['opened_at' => 'datetime', 'closed_at' => 'datetime']; }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function assignments(): HasMany { return $this->hasMany(ReviewAssignment::class); }
    public function decisions(): HasMany { return $this->hasMany(EditorialDecision::class); }
}
