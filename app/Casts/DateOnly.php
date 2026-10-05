<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores dates as plain 'Y-m-d' (Laravel's built-in date cast stores a time part too,
 * which breaks simple equality / range comparisons on SQLite) and returns Carbon.
 */
class DateOnly implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value)->startOfDay() : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') return null;
        return ($value instanceof \DateTimeInterface ? CarbonImmutable::instance($value) : CarbonImmutable::parse($value))->toDateString();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') return null;
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    }
}
