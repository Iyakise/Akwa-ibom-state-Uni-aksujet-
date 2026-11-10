<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Manuscript extends Model
{
    use SoftDeletes;

    protected $fillable = ['reference', 'journal_id', 'submitted_by', 'article_type', 'title', 'abstract', 'cover_letter', 'declarations', 'status', 'submitted_at', 'closed_at'];
    protected function casts(): array { return ['declarations' => 'array', 'submitted_at' => 'datetime', 'closed_at' => 'datetime']; }
    public function getRouteKeyName(): string { return 'id'; }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function article(): HasOne { return $this->hasOne(Article::class); }
    public function authors(): HasMany { return $this->hasMany(ManuscriptAuthor::class)->orderBy('position'); }
    public function versions(): HasMany { return $this->hasMany(ManuscriptVersion::class)->orderByDesc('version_number'); }
    public function files(): HasMany { return $this->hasMany(ManuscriptFile::class); }
    public function statusHistory(): HasMany { return $this->hasMany(ManuscriptStatusHistory::class)->orderByDesc('created_at'); }
    public function editorAssignments(): HasMany { return $this->hasMany(EditorAssignment::class)->whereNull('ended_at'); }
    public function reviewRounds(): HasMany { return $this->hasMany(ReviewRound::class)->orderBy('round_number'); }
    public function decisions(): HasMany { return $this->hasMany(EditorialDecision::class)->orderByDesc('decided_at'); }
}
