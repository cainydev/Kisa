<?php

namespace App\Support\Stats;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for typed statistics value objects stored in a JSON column.
 *
 * Subclasses declare readonly typed properties and map them to/from the
 * stored JSON via fromArray()/toArray().
 */
abstract class StatsData implements Arrayable, Castable
{
    /**
     * @param  array<string, mixed>  $data
     */
    abstract public static function fromArray(array $data): static;

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class(static::class) implements CastsAttributes
        {
            public function __construct(protected string $class) {}

            public function get(Model $model, string $key, mixed $value, array $attributes): ?StatsData
            {
                return $value === null ? null : $this->class::fromArray(json_decode($value, true) ?: []);
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                return $value === null
                    ? null
                    : json_encode($value instanceof Arrayable ? $value->toArray() : $value);
            }
        };
    }

    protected static function dateFrom(?string $value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value) : null;
    }

    /**
     * Calendar days (start/end of the stats window) round-trip as Y-m-d
     * strings parsed with the same explicit timezone on the write and read
     * side. Converting midnight between timezone representations can shift
     * the date by a day (abbreviations like 'CET' resolve to a fixed offset
     * in Carbon but are DST-aware as the default timezone).
     */
    public static function dayFrom(CarbonInterface|string|null $day): ?CarbonImmutable
    {
        if ($day === null) {
            return null;
        }

        $day = $day instanceof CarbonInterface ? $day->toDateString() : $day;

        return CarbonImmutable::parse($day, config('app.timezone'));
    }
}
