<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a bottling process.
 * Has many positions and is fulfilled by a user.
 */
class Bottle extends Model
{
    protected $casts = [
        'date' => 'datetime:d.m.Y',
    ];

    protected $guarded = [];

    /**
     * The user fulfilling the bottle
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
