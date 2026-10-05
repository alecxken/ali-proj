<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'title', 'role', 'auth_source', 'active'];
    protected $hidden = ['password', 'remember_token'];
    protected $appends = ['initials'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean'];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        return strtoupper(mb_substr($parts[0] ?? '', 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'pmo'], true);
    }

    /** Can submit updates, milestones and issues for this project. */
    public function canEditProject(Project $p): bool
    {
        if ($this->isManager()) return true;
        if ($this->role === 'viewer') return false;
        return $p->owner_id === $this->id || $p->members->contains('id', $this->id);
    }

    /** Can change the project's settings (name, owner, team, dates). */
    public function canManageProject(Project $p): bool
    {
        return $this->isManager() || ($this->role === 'owner' && $p->owner_id === $this->id);
    }
}
