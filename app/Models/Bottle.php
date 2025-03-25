<?php

namespace App\Models;

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
    use HasFactory;

    protected $casts = [
        'date' => 'datetime:d.m.Y',
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $bottle) {
            $bottle->description = $bottle->generateDescription();
            $bottle->saveQuietly();
        });

        static::updated(function (self $bottle) {
            $bottle->description = $bottle->generateDescription();
            $bottle->saveQuietly();
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
        foreach ($this->positions as $pos)
            if (!$pos->hasAllBags()) return false;

        return true;
    }
}
