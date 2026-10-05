<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Support\Pulse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    protected $fillable = ['project_id', 'kind', 'title', 'description', 'severity', 'status', 'owner_name',
        'due_date', 'latest_note', 'resolved_on', 'user_id'];
    protected $appends = ['overdue'];

    protected function casts(): array
    {
        return ['due_date' => DateOnly::class, 'resolved_on' => DateOnly::class];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', Pulse::OPEN_ISSUE_STATUS);
    }

    public function scopeBySeverity(Builder $q): Builder
    {
        return $q->orderByRaw("case severity when 'Critical' then 0 when 'High' then 1 when 'Medium' then 2 else 3 end")
            ->orderByRaw('due_date is null, due_date');
    }

    public function getOverdueAttribute(): bool
    {
        return in_array($this->status, Pulse::OPEN_ISSUE_STATUS, true)
            && $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
