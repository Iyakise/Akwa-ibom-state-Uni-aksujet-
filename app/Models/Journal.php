<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'short_title', 'issn', 'eissn', 'description', 'scope', 'contact_email', 'status'];

    public function getRouteKeyName(): string { return 'slug'; }
    public function issues(): HasMany { return $this->hasMany(Issue::class); }
    public function manuscripts(): HasMany { return $this->hasMany(Manuscript::class); }
    public function articles(): HasMany { return $this->hasMany(Article::class); }
}
