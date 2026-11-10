<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Correspondence extends Model
{
    protected $table = 'correspondence';

    protected $fillable = ['manuscript_id', 'sender_id', 'recipient_id', 'recipient_email', 'audience', 'subject', 'body', 'sent_at'];
    protected function casts(): array { return ['sent_at' => 'datetime']; }
    public function manuscript(): BelongsTo { return $this->belongsTo(Manuscript::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }
    public function attachments(): HasMany { return $this->hasMany(CorrespondenceAttachment::class); }
}
