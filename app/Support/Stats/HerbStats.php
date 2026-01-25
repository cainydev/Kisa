<?php

namespace App\Support\Stats;

use App\Models\Herb;
use App\Services\HerbStatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class HerbStats
{
    protected string $baseKey;

    public function __construct(protected Herb $herb)
    {
        $this->baseKey = HerbStatisticsService::CACHE_PREFIX . ":{$herb->id}";
    }

    public static function for(Herb $herb): self
    {
        return new self($herb);
    }

    public function stock(): TimeSeriesQuery
    {
        return new TimeSeriesQuery(
            $this->getFromCache('stock:daily', collect()),
            'last'
        );
    }

    protected function getFromCache(string $subKey, mixed $default = null): mixed
    {
        return Cache::get("{$this->baseKey}:{$subKey}", $default);
    }

    public function usage(): TimeSeriesQuery
    {
        return new TimeSeriesQuery(
            $this->getFromCache('usage:daily', collect()),
            'sum' // ✅ Default to 'sum' value (Accumulation)
        );
    }

    public function currentStock(): float
    {
        return (float)$this->getFromCache('stock:current', 0);
    }

    public function totalUsage(): float
    {
        return (float)$this->getFromCache('usage:total', 0);
    }

    public function estimatedDepletionDate(): ?Carbon
    {
        $val = $this->getFromCache('depletion_date');
        return $val ? Carbon::parse($val) : null;
    }
}
