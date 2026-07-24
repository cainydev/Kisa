<?php

namespace App\Support\Stats;

use Carbon\CarbonInterface;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class TimeSeriesQuery
{
    protected Collection $data;

    protected ?string $start;

    protected ?string $end;

    protected bool $aggregated = false;

    /**
     * Day keys and bucket labels are identical for every series sharing a
     * window (the stats window is global), so they are memoized statically.
     * Bounded: reset when windows change, e.g. across the midnight boundary.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $dayKeyCache = [];

    /** @var array<string, array<string, string>> */
    protected static array $bucketLabelCache = [];

    /**
     * @param  Collection|array<string, float>  $data  Sparse daily values keyed by Y-m-d date
     * @param  string  $defaultAggregator  'sum' for flows (sales/usage), 'last' for levels (stock)
     * @param  CarbonInterface|string|null  $start  First day of the series window
     * @param  CarbonInterface|string|null  $end  Last day of the series window
     */
    public function __construct(
        Collection|array $data,
        protected string $defaultAggregator = 'sum',
        CarbonInterface|string|null $start = null,
        CarbonInterface|string|null $end = null,
    ) {
        $this->data = collect($data)->sortKeys();
        $this->start = static::toDayString($start) ?? $this->data->keys()->first();
        $this->end = static::toDayString($end) ?? $this->data->keys()->last();
    }

    /**
     * Dense values for the current window: one value per day, or one per
     * bucket after lastWeeks()/lastMonths() aggregation.
     */
    public function get(): Collection
    {
        return $this->aggregated ? $this->data : $this->dense();
    }

    public function toChartArray(): array
    {
        return $this->get()->values()->toArray();
    }

    /**
     * Narrow the window to the last X days of raw daily data.
     */
    public function lastDays(int $count): self
    {
        if ($this->end !== null) {
            $windowStart = new DateTimeImmutable($this->end)->modify('-'.($count - 1).' days')->format('Y-m-d');
            $this->start = $this->start === null ? $windowStart : max($this->start, $windowStart);
        }

        return $this;
    }

    /**
     * Aggregate by week and take the last X weeks. The 'o-W' format keys by
     * ISO year-week (e.g. 2025-42) so weeks don't mix across years.
     */
    public function lastWeeks(int $count, ?string $aggregator = null): self
    {
        return $this->aggregate('o-W', $aggregator, $count);
    }

    /**
     * Aggregate by month and take the last X months.
     */
    public function lastMonths(int $count, ?string $aggregator = null): self
    {
        return $this->aggregate('Y-m', $aggregator, $count);
    }

    protected function aggregate(string $format, ?string $method, int $count): self
    {
        $method ??= $this->defaultAggregator;
        $labels = ($this->start !== null && $this->end !== null)
            ? static::bucketLabels($this->start, $this->end, $format)
            : [];

        $this->data = $this->dense()
            ->groupBy(fn ($value, string $date) => $labels[$date])
            ->map(fn (Collection $bucket) => match ($method) {
                'avg' => $bucket->avg(),
                'min' => $bucket->min(),
                'max' => $bucket->max(),
                'last' => $bucket->last(),
                'first' => $bucket->first(),
                default => $bucket->sum(),
            })
            ->sortKeys()
            ->take(-$count);

        $this->aggregated = true;

        return $this;
    }

    /**
     * Expand the sparse data to one value per day over the window. Flows fill
     * gaps with zero; levels carry the previous value forward, seeded by the
     * last known value before the window start.
     */
    protected function dense(): Collection
    {
        if ($this->start === null || $this->end === null) {
            return collect();
        }

        $sparse = $this->data->all();
        $dense = [];

        if ($this->defaultAggregator === 'last') {
            $carry = 0;

            foreach ($sparse as $date => $value) {
                if ($date >= $this->start) {
                    break;
                }

                $carry = $value;
            }

            foreach (static::dayKeys($this->start, $this->end) as $key) {
                $carry = $sparse[$key] ?? $carry;
                $dense[$key] = $carry;
            }
        } else {
            foreach (static::dayKeys($this->start, $this->end) as $key) {
                $dense[$key] = $sparse[$key] ?? 0;
            }
        }

        return collect($dense);
    }

    /**
     * Calendar dates are handled as plain Y-m-d strings throughout: the
     * configured 'CET' timezone renders DST instants with shifted offsets,
     * which silently moves midnight onto the previous day when converting
     * between Carbon flavors.
     */
    protected static function toDayString(CarbonInterface|string|null $day): ?string
    {
        if ($day instanceof CarbonInterface) {
            return $day->toDateString();
        }

        return $day === null || $day === '' ? null : $day;
    }

    /**
     * @return array<int, string> Every Y-m-d day from $start to $end inclusive
     */
    protected static function dayKeys(string $start, string $end): array
    {
        $cacheKey = "{$start}|{$end}";

        if (isset(static::$dayKeyCache[$cacheKey])) {
            return static::$dayKeyCache[$cacheKey];
        }

        if (count(static::$dayKeyCache) >= 3) {
            static::$dayKeyCache = [];
        }

        $keys = [];
        $day = new DateTimeImmutable($start);
        $endDay = new DateTimeImmutable($end);

        while ($day <= $endDay) {
            $keys[] = $day->format('Y-m-d');
            $day = $day->modify('+1 day');
        }

        return static::$dayKeyCache[$cacheKey] = $keys;
    }

    /**
     * @return array<string, string> Y-m-d day => bucket label (e.g. 2025-42)
     */
    protected static function bucketLabels(string $start, string $end, string $format): array
    {
        $cacheKey = "{$start}|{$end}|{$format}";

        if (isset(static::$bucketLabelCache[$cacheKey])) {
            return static::$bucketLabelCache[$cacheKey];
        }

        if (count(static::$bucketLabelCache) >= 6) {
            static::$bucketLabelCache = [];
        }

        $labels = [];

        foreach (static::dayKeys($start, $end) as $key) {
            $labels[$key] = new DateTimeImmutable($key)->format($format);
        }

        return static::$bucketLabelCache[$cacheKey] = $labels;
    }
}
