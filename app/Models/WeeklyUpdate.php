<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyUpdate extends Model
{
    protected $fillable = ['project_id', 'week_start', 'user_id', 'rag', 'progress', 'phase', 'summary',
        'achievements', 'critical_path', 'next_steps', 'support_needed', 'metrics'];

    protected function casts(): array
    {
        return ['week_start' => DateOnly::class, 'metrics' => 'array', 'progress' => 'integer'];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
