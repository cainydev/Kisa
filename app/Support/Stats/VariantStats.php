<?php

namespace App\Support\Stats;

use App\Models\Variant;
use Carbon\CarbonImmutable;

class VariantStats extends StatsData
{
    /**
     * @param  array<string, float>  $sales  Sparse daily units sold, keyed by Y-m-d
     * @param  array<string, float>  $restocks  Sparse daily units produced, keyed by Y-m-d
     * @param  array<string, float>  $stock  Sparse stock levels on the days they changed, keyed by Y-m-d
     */
    public function __construct(
        public readonly ?CarbonImmutable $start = null,
        public readonly ?CarbonImmutable $end = null,
        public readonly ?CarbonImmutable $generatedAt = null,
        public readonly float $currentStock = 0,
        public readonly array $sales = [],
        public readonly array $restocks = [],
        public readonly array $stock = [],
        public readonly float $totalSales = 0,
        public readonly float $averageDailySales = 0,
        public readonly ?CarbonImmutable $depletionDate = null,
        public readonly ?CarbonImmutable $nextSaleDate = null,
    ) {}

    public static function for(Variant $variant): self
    {
        return $variant->stats ?? new self;
    }

    public static function fromArray(array $data): static
    {
        return new self(
            start: self::dayFrom($data['start'] ?? null),
            end: self::dayFrom($data['end'] ?? null),
            generatedAt: self::dateFrom($data['generated_at'] ?? null),
            currentStock: (float) ($data['current_stock'] ?? 0),
            sales: $data['sales'] ?? [],
            restocks: $data['restocks'] ?? [],
            stock: $data['stock'] ?? [],
            totalSales: (float) ($data['sales_total'] ?? 0),
            averageDailySales: (float) ($data['sales_avg_recent'] ?? 0),
            depletionDate: self::dateFrom($data['depletion_date'] ?? null),
            nextSaleDate: self::dateFrom($data['next_sale_date'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start?->toDateString(),
            'end' => $this->end?->toDateString(),
            'generated_at' => $this->generatedAt?->toIso8601String(),
            'current_stock' => $this->currentStock,
            'sales' => (object) $this->sales,
            'restocks' => (object) $this->restocks,
            'stock' => (object) $this->stock,
            'sales_total' => $this->totalSales,
            'sales_avg_recent' => $this->averageDailySales,
            'depletion_date' => $this->depletionDate?->toIso8601String(),
            'next_sale_date' => $this->nextSaleDate?->toIso8601String(),
        ];
    }

    /**
     * Sales history (units sold per day).
     */
    public function sales(): TimeSeriesQuery
    {
        return new TimeSeriesQuery($this->sales, 'sum', $this->start, $this->end);
    }

    /**
     * Production history (units bottled per day).
     */
    public function production(): TimeSeriesQuery
    {
        return new TimeSeriesQuery($this->restocks, 'sum', $this->start, $this->end);
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

    public function totalSales(): float
    {
        return $this->totalSales;
    }

    public function averageDailySales(): float
    {
        return $this->averageDailySales;
    }

    public function averageMonthlySales(): float
    {
        return $this->averageDailySales * 30;
    }

    public function estimatedDepletionDate(): ?CarbonImmutable
    {
        return $this->depletionDate;
    }

    public function nextSaleDate(): ?CarbonImmutable
    {
        return $this->nextSaleDate;
    }
}
