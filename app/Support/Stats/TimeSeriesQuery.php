<?php

namespace App\Support\Stats;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeSeriesQuery
{
    /**
     * @param Collection $data
     * @param string $defaultAggregator 'sum' for Usage, 'last' for Stock
     */
    public function __construct(
        protected Collection $data,
        protected string     $defaultAggregator = 'sum'
    )
    {
    }

    public function get(): Collection
    {
        return $this->data;
    }

    public function toChartArray(): array
    {
        return $this->data->values()->toArray();
    }

    /**
     * Get the last X days of raw data
     */
    public function lastDays(int $count): self
    {
        // Slice the raw daily data
        $this->data = $this->sliceData($this->data, $count);
        return $this;
    }

    /**
     * Internal: Slice helper (Sort desc -> Take -> Sort asc)
     */
    protected function sliceData(Collection $data, int $count): Collection
    {
        return $data->sortKeysDesc()->take($count)->sortKeys();
    }

    /**
     * Aggregate by Week and take the last X weeks
     */
    public function lastWeeks(int $count, ?string $aggregator = null): self
    {
        // Format 'o-W' ensures Year-Week (e.g., 2025-42) so weeks don't mix across years
        $this->data = $this->aggregate('o-W', $aggregator);
        $this->data = $this->sliceData($this->data, $count);

        return $this;
    }

    /**
     * Internal: Aggregation Logic
     */
    protected function aggregate(string $format, ?string $method): Collection
    {
        $method = $method ?? $this->defaultAggregator;

        return $this->data
            ->groupBy(fn($val, $date) => Carbon::parse($date)->format($format))
            ->map(fn(Collection $chunk) => match ($method) {
                'avg' => $chunk->avg(),
                'min' => $chunk->min(),
                'max' => $chunk->max(),
                'last' => $chunk->sortKeys()->last(),
                'first' => $chunk->sortKeys()->first(),
                default => $chunk->sum(),
            });
    }

    /**
     * Aggregate by Month and take the last X months
     */
    public function lastMonths(int $count, ?string $aggregator = null): self
    {
        $this->data = $this->aggregate('Y-m', $aggregator);
        $this->data = $this->sliceData($this->data, $count);

        return $this;
    }
}
