<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrespondenceAttachment extends Model
{
    protected $fillable = ['correspondence_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'];
    public function correspondence(): BelongsTo { return $this->belongsTo(Correspondence::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
