<?php

namespace App\Traits;

use App\Services\AbstractStatistics;
use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use function value;

trait CachedAttributes
{
    /**
     * Get all the "Attribute" return typed attribute mutator methods.
     * Needed to prevent errors trying to invoke cached without args.
     *
     * @param mixed $class
     * @return array
     * @throws ReflectionException
     */
    protected static function getAttributeMarkedMutatorMethods($class): array
    {
        $instance = is_object($class) ? $class : new $class;

        return collect(new ReflectionClass($instance)->getMethods())
            ->filter(function (ReflectionMethod $method) use ($instance) {
                $returnType = $method->getReturnType();

                if ($method->getName() === 'cached') return false;
                if ($returnType instanceof ReflectionNamedType &&
                    $returnType->getName() === Attribute::class) {
                    if (is_callable($method->invoke($instance)->get)) {
                        return true;
                    }
                }

                return false;
            })->map->name->values()->all();
    }

    /**
     * Helper to create a cached Eloquent Attribute.
     *
     * @param string $key The name of the metric (used for cached key).
     * @param mixed $default If a Closure, it's executed on cached miss to get the fresh value.
     *                           It receives no arguments and should return the value.
     *                           If not a Closure, this value itself is used as the default/fallback if cached misses.
     * @param Closure|null $onChange A closure executed when the attribute is set *and* the new value
     *                               is different from the currently cached value (or if no old value was cached).
     *                               It receives two arguments: ($newValue, $oldValueFromCache).
     * @param int|null $cacheDuration Custom cached duration in seconds. Null uses model/trait default.
     * @return Attribute
     */
    public function cached(
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
     * Default cached duration in seconds for attributes using this trait.
     * Can be overridden in the model using the trait.
     */
    public function getDefaultAttributeCacheDuration(): int
    {
        return AbstractStatistics::CACHE_MEDIUM;
    }

    /**
     * Generates a standardized cached key for an attribute.
     *
     * @param string $metric The specific metric or attribute name.
     * @return string
     */
    public function getCacheKey(string $metric): string
    {
        if (!$this->exists()) {
            throw new \RuntimeException('Cannot generate cached key for non-existing model instance.');
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
