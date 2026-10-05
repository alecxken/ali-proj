<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name', 'code', 'programme', 'description', 'phase', 'status', 'owner_id',
        'start_date', 'target_date', 'sort_order'];

    protected function casts(): array
    {
        return ['start_date' => DateOnly::class, 'target_date' => DateOnly::class];
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function members(): BelongsToMany { return $this->belongsToMany(User::class); }
    public function milestones(): HasMany { return $this->hasMany(Milestone::class)->orderByRaw('due_date is null, due_date'); }
    public function updates(): HasMany { return $this->hasMany(WeeklyUpdate::class)->orderByDesc('week_start'); }
    public function issues(): HasMany { return $this->hasMany(Issue::class); }
}
