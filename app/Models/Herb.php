<?php

namespace App\Models;

use App\Traits\CachedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// AbstractStatistics is used via the trait

// Still needed for estimatedDepletionDate

class Herb extends Model
{
    use HasFactory, CachedAttributes;

    protected $guarded = [];

    public function toSearchableArray(): array
    {
        $prods = $this->products->pluck('name')->implode(', ');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullname' => $this->fullname,
            'prods' => $prods,
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Bag::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected function dailyUsageStats(): Attribute
    {
        return $this->cache('daily', default: collect());
    }

    protected function weeklyUsageStats(): Attribute
    {
        return $this->cache('weekly', default: collect());
    }

    protected function monthlyUsageStats(): Attribute
    {
        return $this->cache('monthly', default: collect());
    }

    protected function yearlyUsageStats(): Attribute
    {
        return $this->cache('yearly', default: collect());
    }

    protected function averageDailyUsage(): Attribute
    {
        return $this->cache('daily:avg', default: 0.0);
    }

    protected function averageWeeklyUsage(): Attribute
    {
        return $this->cache('weekly:avg', default: 0.0);
    }

    protected function averageMonthlyUsage(): Attribute
    {
        return $this->cache('monthly:avg', default: 0.0);
    }

    protected function averageYearlyUsage(): Attribute
    {
        return $this->cache('yearly:avg', default: 0.0);
    }

    protected function currentStock(): Attribute
    {
        return $this->cache('current', default: 0.0);
    }

    protected function totalUsage(): Attribute
    {
        return $this->cache('total', default: 0.0);
    }

    protected function estimatedDepletionDate(): Attribute
    {
        return $this->cache('depleted');

        /*
        $cacheKey = $this->getCacheKey('depleted');
        $duration = $this->getDefaultAttributeCacheDuration();

        return Attribute::make(
            get: function () use ($cacheKey) { // Eloquent value and attributes not used
                $cachedDateString = Cache::get($cacheKey);
                return $cachedDateString ? Carbon::parse($cachedDateString) : null;
            },
            set: function (?Carbon $carbonValue) use ($cacheKey, $duration) {
                Cache::put($cacheKey, $carbonValue?->toIso8601String(), $duration);
            }
        );*/
    }
}
