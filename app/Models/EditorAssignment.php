<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorAssignment extends Model
{
    protected $fillable = ['manuscript_id', 'editor_id', 'assigned_by', 'assignment_role', 'assigned_at', 'ended_at'];
    protected function casts(): array { return ['assigned_at' => 'datetime', 'ended_at' => 'datetime']; }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'editor_id'); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
