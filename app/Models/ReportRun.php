<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRun extends Model
{
    protected $fillable = ['week_start', 'format', 'programme', 'file', 'user_id'];

    protected function casts(): array
    {
        return ['week_start' => DateOnly::class];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
