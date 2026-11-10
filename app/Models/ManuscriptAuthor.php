<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManuscriptAuthor extends Model
{
    protected $fillable = ['manuscript_id', 'user_id', 'position', 'given_name', 'family_name', 'email', 'affiliation', 'orcid', 'is_corresponding'];
    protected function casts(): array { return ['is_corresponding' => 'boolean']; }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
