<?php

namespace App\Support\Stats;

use App\Models\Variant;
use App\Services\VariantStatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class VariantStats
{
    protected string $baseKey;

    public function __construct(protected Variant $variant)
    {
        $this->baseKey = VariantStatisticsService::CACHE_PREFIX . ":{$variant->id}";
    }

    public static function for(Variant $variant): self
    {
        return new self($variant);
    }

    /**
     * Query Sales History (Daily, Weekly, Monthly)
     */
    public function sales(): TimeSeriesQuery
    {
        return new TimeSeriesQuery(
            $this->getFromCache('sales:daily', collect())
        );
    }

    protected function getFromCache(string $subKey, mixed $default = null): mixed
    {
        return Cache::get("{$this->baseKey}:{$subKey}", $default);
    }

    /**
     * Query Stock History (Reconstructed)
     */
    public function stock(): TimeSeriesQuery
    {
        return new TimeSeriesQuery(
            $this->getFromCache('stock:daily', collect())
        );
    }

    public function currentStock(): float
    {
        return (float)$this->getFromCache('stock:current', 0);
    }

    public function totalSales(): float
    {
        return (float)$this->getFromCache('sales:total', 0);
    }

    public function averageDailySales(): float
    {
        // You can return the recent rate or calculated all-time avg
        return (float)$this->getFromCache('sales:avg_recent', 0);
    }

    public function production(): TimeSeriesQuery
    {
        return new TimeSeriesQuery(
            $this->getFromCache('restock:daily', collect())
        );
    }
    
    public function estimatedDepletionDate(): ?Carbon
    {
        $val = $this->getFromCache('depletion_date');
        return $val ? Carbon::parse($val) : null;
    }

    public function nextSaleDate(): ?Carbon
    {
        $val = $this->getFromCache('next_sale_date');
        return $val ? Carbon::parse($val) : null;
    }
}
