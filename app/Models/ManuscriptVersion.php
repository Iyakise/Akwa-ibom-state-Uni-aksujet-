<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManuscriptVersion extends Model
{
    protected $fillable = ['manuscript_id', 'version_number', 'submitted_by', 'title', 'abstract', 'cover_letter', 'author_response', 'submitted_at'];
    protected function casts(): array { return ['submitted_at' => 'datetime']; }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function files(): HasMany { return $this->hasMany(ManuscriptFile::class); }
}
