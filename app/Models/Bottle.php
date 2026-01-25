<?php

namespace App\Models;

use App\Traits\CachedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function str;

/**
 * Represents a bottling process.
 * Has many positions and is fulfilled by a user.
 */
class Bottle extends Model
{
    use HasFactory, CachedAttributes;

    protected $casts = [
        'date' => 'datetime:d.m.Y',
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $bottle) {
            $bottle->description = $bottle->generateDescription();
        });

        static::updated(function (self $bottle) {
            $bottle->description = $bottle->generateDescription();
        });
    }

    public function generateDescription(): string
    {
        return $this
            ->positions()
            ->with(['variant.product', 'variant.product.type'])
            ->get()
            ->map(function ($pos) {
                $name = str($pos->variant->product->name);
                if ($name->startsWith('Nr.')) {
                    return $name->take(6);
                } else if ($name->startsWith('Ruths ')) {
                    return $name->after('Ruths ')->limit(12, '.');
                } else if ($name->startsWith('Bio ')) {
                    return $name->after('Bio ')->limit(12, '.');
                } else {
                    return $name->limit(12, '.');
                }
            })
            ->unique()
            ->sort()
            ->join(', ');
    }

    /**
     * The bottle positions
     * @return HasMany
     */
    public function positions(): HasMany
    {
        return $this->hasMany(BottlePosition::class);
    }

    public function description(): Attribute
    {
        return $this->cachedAttribute(
            key: 'description',
            default: fn() => $this->generateDescription(),
            cacheDuration: 60 * 60 * 24 * 31, // 31 days
            saveOnMiss: true
        )();
    }

    /**
     * The user fulfilling the bottle
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculates if all positions are finished
     * @return bool
     */
    public function finished(): bool
    {
        return array_all($this->positions, fn($pos) => $pos->uploaded);
    }
}
