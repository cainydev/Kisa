<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a bottle position to the bag a herb was drawn from. `amount` is the
 * grams drawn, snapshotted at bottling so later recipe or variant edits
 * don't change historical usage.
 */
class Ingredient extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ingredient) {
            $ingredient->amount ??= $ingredient->computeAmount();
        });
    }

    /**
     * Grams this bottling draws from the bag per the current recipe:
     * variant.size × count × recipe share. Only used to seed `amount` at
     * creation and to recompute it when a position's count changes.
     */
    public function computeAmount(): float
    {
        $position = $this->position;
        $variant = $position?->variant;
        $percentage = (float) ($variant?->product?->herbs->firstWhere('id', $this->herb_id)?->pivot->percentage ?? 0);

        return round(($variant?->size ?? 0) * ($position?->count ?? 0) * ($percentage / 100), 2);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(BottlePosition::class, 'bottle_position_id');
    }

    public function herb(): BelongsTo
    {
        return $this->belongsTo(Herb::class);
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(Bag::class)->withTrashed();
    }
}
