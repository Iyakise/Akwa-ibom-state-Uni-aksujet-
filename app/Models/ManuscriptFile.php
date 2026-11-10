<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManuscriptFile extends Model
{
    protected $fillable = ['manuscript_id', 'manuscript_version_id', 'uploaded_by', 'kind', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256'];
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function version(): BelongsTo { return $this->belongsTo(ManuscriptVersion::class, 'manuscript_version_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
