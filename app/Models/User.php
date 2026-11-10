<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'affiliation', 'orcid', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot(['granted_by', 'granted_at']);
    }

    public function hasRole(string $role): bool
    {
        return $this->account_status === 'active' && $this->roles()->where('name', $role)->exists();
    }

    public function isEditorialAllowlisted(): bool
    {
        return in_array(strtolower($this->email), config('aksujet.editorial_allowlist', []), true);
    }

    /** @param list<string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        if ($this->account_status !== 'active' || $roles === []) {
            return false;
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function assignRole(string $role, ?int $grantedBy = null): void
    {
        $roleModel = Role::query()->where('name', $role)->firstOrFail();
        $this->roles()->syncWithoutDetaching([
            $roleModel->id => ['granted_by' => $grantedBy, 'granted_at' => now()],
        ]);
    }

    public function submittedManuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class, 'submitted_by');
    }

    public function editorAssignments(): HasMany
    {
        return $this->hasMany(EditorAssignment::class, 'editor_id');
    }

    public function reviewAssignments(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'reviewer_id');
    }
}
