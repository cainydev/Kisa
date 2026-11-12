<?php

namespace App\Support\Stats;

use App\Models\Herb;
use Carbon\CarbonImmutable;

class HerbStats extends StatsData
{
    /**
     * @param  array<string, float>  $usage  Sparse daily usage in grams (production + discards), keyed by Y-m-d
     * @param  array<string, float>  $stock  Sparse stock levels in grams on the days they changed, keyed by Y-m-d
     */
    public function __construct(
        public readonly ?CarbonImmutable $start = null,
        public readonly ?CarbonImmutable $end = null,
        public readonly ?CarbonImmutable $generatedAt = null,
        public readonly float $currentStock = 0,
        public readonly array $usage = [],
        public readonly array $stock = [],
        public readonly float $totalUsage = 0,
        public readonly float $averageDailyUsage = 0,
        public readonly float $averageWeeklyUsage = 0,
        public readonly float $averageMonthlyUsage = 0,
        public readonly ?CarbonImmutable $depletionDate = null,
        public readonly ?float $forecastDailyUsage = null,
    ) {}

    public static function for(Herb $herb): self
    {
        return $herb->stats ?? new self;
    }

    public static function fromArray(array $data): static
    {
        return new self(
            start: self::dayFrom($data['start'] ?? null),
            end: self::dayFrom($data['end'] ?? null),
            generatedAt: self::dateFrom($data['generated_at'] ?? null),
            currentStock: (float) ($data['current_stock'] ?? 0),
            usage: $data['usage'] ?? [],
            stock: $data['stock'] ?? [],
            totalUsage: (float) ($data['usage_total'] ?? 0),
            averageDailyUsage: (float) ($data['usage_avg_daily'] ?? 0),
            averageWeeklyUsage: (float) ($data['usage_avg_weekly'] ?? 0),
            averageMonthlyUsage: (float) ($data['usage_avg_monthly'] ?? 0),
            depletionDate: self::dateFrom($data['depletion_date'] ?? null),
            forecastDailyUsage: isset($data['usage_forecast_daily']) ? (float) $data['usage_forecast_daily'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start?->toDateString(),
            'end' => $this->end?->toDateString(),
            'generated_at' => $this->generatedAt?->toIso8601String(),
            'current_stock' => $this->currentStock,
            'usage' => (object) $this->usage,
            'stock' => (object) $this->stock,
            'usage_total' => $this->totalUsage,
            'usage_avg_daily' => $this->averageDailyUsage,
            'usage_avg_weekly' => $this->averageWeeklyUsage,
            'usage_avg_monthly' => $this->averageMonthlyUsage,
            'depletion_date' => $this->depletionDate?->toIso8601String(),
            'usage_forecast_daily' => $this->forecastDailyUsage,
        ];
    }

    /**
     * Usage history (grams consumed per day, production + discards).
     */
    public function usage(): TimeSeriesQuery
    {
        return new TimeSeriesQuery($this->usage, 'sum', $this->start, $this->end);
    }

    /**
     * Reconstructed stock history (level at end of each day).
     */
    public function stock(): TimeSeriesQuery
    {
        return new TimeSeriesQuery($this->stock, 'last', $this->start, $this->end);
    }

    public function currentStock(): float
    {
        return $this->currentStock;
    }

    public function totalUsage(): float
    {
        return $this->totalUsage;
    }

    /**
     * Average grams per day over the whole stats window.
     */
    public function averageDailyUsage(): float
    {
        return $this->averageDailyUsage;
    }

    /**
     * Average grams per week over the last 52 weeks.
     */
    public function averageWeeklyUsage(): float
    {
        return $this->averageWeeklyUsage;
    }

    /**
     * Average grams per month over the last 12 months.
     */
    public function averageMonthlyUsage(): float
    {
        return $this->averageMonthlyUsage;
    }

    public function estimatedDepletionDate(): ?CarbonImmutable
    {
        return $this->depletionDate;
    }

    /**
     * Grams per day the depletion forecast runs on: the last 90 days' average,
     * or the all-time average when the herb wasn't used recently. Stats
     * generated before this was stored fall back to the all-time average.
     */
    public function forecastDailyUsage(): float
    {
        return $this->forecastDailyUsage ?? $this->averageDailyUsage;
    }

    /**
     * Stock left once the reserved grams are taken out. Negative when more is
     * promised than is on the shelf.
     */
    public function availableStock(float $reserved = 0): float
    {
        return $this->currentStock - $reserved;
    }

    /**
     * Depletion date once `$reserved` grams are spoken for: today when the
     * reservation already eats the whole stock, otherwise the remaining stock
     * extrapolated at the forecast rate.
     */
    public function depletionDateWith(float $reserved): ?CarbonImmutable
    {
        if ($reserved <= 0) {
            return $this->depletionDate;
        }

        $available = $this->availableStock($reserved);

        if ($available <= 0) {
            return CarbonImmutable::today();
        }

        $rate = $this->forecastDailyUsage();

        if ($rate <= 0) {
            return $this->depletionDate;
        }

        return CarbonImmutable::now()->addDays($available / $rate);
    }
}
