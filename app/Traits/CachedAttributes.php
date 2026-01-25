<?php

namespace App\Traits;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use function value;

trait CachedAttributes
{
    /**
     * Get all the "Attribute" return typed attribute mutator methods.
     * Needed to prevent errors trying to invoke cachedAttribute without args.
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

                if ($returnType instanceof ReflectionNamedType &&
                    $returnType->getName() === Attribute::class &&
                    $method->getName() === 'cachedAttribute') {
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
     * @param string $key The name of the metric (used for cache key).
     * @param mixed $default If a Closure, it's executed on cache miss to get the fresh value.
     *                           It receives no arguments and should return the value.
     *                           If not a Closure, this value itself is used as the default/fallback on cache miss.
     * @param Closure|null $onChange A closure executed when the attribute is set *and* the new value
     *                               is different from the currently cached value (or if no old value was cached).
     *                               It receives two arguments: ($newValue, $oldValueFromCache).
     * @param int|null $cacheDuration Custom cache duration in seconds. Null uses model/trait default.
     */
    public function cachedAttribute(
        string   $key,
        mixed    $default = null,
        ?string  $cast = null,
        ?Closure $onChange = null,
        ?int     $cacheDuration = null,
        bool     $saveOnMiss = false,
    ): Closure
    {
        $duration = $cacheDuration ?? $this->getDefaultAttributeCacheDuration();

        $key = $this->getCacheKey($key);

        $this->mergeCasts([$key => $cast ?: $this->guessCastType($default) ?: 'string']);

        return fn() => Attribute::make(
            get: function ($value, array $attributes) use ($default, $key, $onChange, $duration, $saveOnMiss) {
                if (!($value = Cache::get($key))) {
                    $value = value($default, [$key, $duration]);

                    if ($saveOnMiss) {
                        Cache::put($key, $value, $duration);

                        if ($onChange instanceof Closure) {
                            $onChange($value, null);
                        }
                    }
                }

                return $this->castAttribute($key, $value);
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
    public function getDefaultAttributeCacheDuration(): int|null
    {
        return 60 * 60 * 24; // 24h
    }

    /**
     * Generates a standardized cache key for an attribute.
     *
     * @param string $metric The specific metric or attribute name.
     * @return string
     */
    public function getCacheKey(string $metric): string
    {
        if (!$this->exists) {
            throw new RuntimeException('Cannot generate cachedAttribute key for non-existing model instance.');
        }

        $modelName = Str::snake(class_basename($this));
        $trimmedMetric = ltrim($metric, ':');

        // >once< prevents infinite recursion here. Otherwise, getKey would
        // trigger reevaluation of all the classes attributes, including the cached ones,
        // which in turn would eventually trigger this function again.
        $identifier = once(fn() => $this->getKey());

        return "{$modelName}:{$identifier}:{$trimmedMetric}";
    }

    /**
     * Tries to guess the cast type, based on a value
     */
    public function guessCastType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            is_array($value) => 'array',
            $value instanceof Collection => 'collection',
            $value instanceof DateTimeInterface => 'datetime',
            $value instanceof BackedEnum => get_class($value),
            default => 'string',
        };
    }
}
