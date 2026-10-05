<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = ['project_id', 'title', 'due_date', 'status', 'completed_on'];
    protected $appends = ['overdue'];

    protected function casts(): array
    {
        return ['due_date' => DateOnly::class, 'completed_on' => DateOnly::class];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    public function getOverdueAttribute(): bool
    {
        return $this->status !== 'Done' && $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
