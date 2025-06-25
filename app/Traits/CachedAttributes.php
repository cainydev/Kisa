<?php

namespace App\Traits;

use App\Services\AbstractStatistics;
use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use function value;

trait CachedAttributes
{
    /**
     * Helper to create a cached Eloquent Attribute.
     *
     * @param string $key The name of the metric (used for cache key).
     * @param mixed $default If a Closure, it's executed on cache miss to get the fresh value.
     *                           It receives no arguments and should return the value.
     *                           If not a Closure, this value itself is used as the default/fallback if cache misses.
     * @param Closure|null $onChange A closure executed when the attribute is set *and* the new value
     *                               is different from the currently cached value (or if no old value was cached).
     *                               It receives two arguments: ($newValue, $oldValueFromCache).
     * @param int|null $cacheDuration Custom cache duration in seconds. Null uses model/trait default.
     * @return Attribute
     */
    public function cache(
        string   $key,
        mixed    $default = null,
        ?Closure $onChange = null,
        ?int     $cacheDuration = null,
    ): Attribute
    {
        $duration = $cacheDuration ?? $this->getDefaultAttributeCacheDuration();

        $key = $this->getCacheKey($key);

        return Attribute::make(
            get: function ($value, array $attributes) use ($default, $key, $duration) {
                return Cache::get($key) ?? value($default, [$key, $duration]);
            },
            set: function ($value) use ($onChange, $key, $duration) {
                $old = Cache::get($key);

                Cache::put($key, $value, $duration);

                if ($old !== $value && $onChange instanceof Closure) {
                    $onChange($value, $old);
                }
            }
        )->withoutObjectCaching();
    }

    /**
     * Default cache duration in seconds for attributes using this trait.
     * Can be overridden in the model using the trait.
     */
    public function getDefaultAttributeCacheDuration(): int
    {
        return AbstractStatistics::CACHE_MEDIUM;
    }

    /**
     * Generates a standardized cache key for an attribute.
     *
     * @param string $metric The specific metric or attribute name.
     * @return string
     */
    public function getCacheKey(string $metric): string
    {
        if (!$this->exists()) {
            throw new \RuntimeException('Cannot generate cache key for non-existing model instance.');
        }

        $modelName = Str::snake(class_basename($this));
        $trimmedMetric = ltrim($metric, ':');

        // >once< prevents infinite recursion here. Otherwise, getKey would
        // trigger reevaluation of all the classes attributes, including the cached ones,
        // which in turn would eventually trigger this function again.
        $identifier = once(fn() => $this->getKey());

        return "{$modelName}:{$identifier}:{$trimmedMetric}";
    }
}
