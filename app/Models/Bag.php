<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Bag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $with = ['herb'];

    protected $casts = [
        'bestbefore' => 'date:Y-m-d',
        'steamed' => 'date:Y-m-d',
    ];

    public function title(): Attribute
    {
        return new Attribute(get: fn () => $this->herb->name.' '.$this->specification.' ('.$this->charge.')');
    }

    public function discard(): bool
    {
        $this->update(['trashed' => $this->getCurrent()]);

        return $this->delete();
    }

    /**
     * Returns the current amount in this bag in g
     */
    public function getCurrent(): float
    {
        $sum = 0;
        foreach ($this->ingredients as $i) {
            $variant = $i->position->variant;
            foreach ($variant->product->herbs as $herb) {
                if ($herb->id == $this->herb->id) {
                    $sum += ($variant->size * $i->position->count) * ($herb->pivot->percentage / 100);
                }
            }
        }

        return $this->size - $sum;
    }

    /**
     * Get the computed identifier of this bag.
     */
    public function identifier(): Attribute
    {
        return new Attribute(get: function () {
            return $this->specification.' '.$this->herb->fullname.$this->getSizeInKilo();
        });
    }

    /**
     * Returns the size formatted in kg
     */
    public function getSizeInKilo(): string
    {
        return sprintf('%.1fkg', $this->size / 1000);
    }

    /**
     * Returns the herb that this bag contains
     */
    public function herb(): BelongsTo
    {
        return $this->belongsTo(Herb::class);
    }

    /**
     * Returns the delivery this bag was delivered in
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Returns all the Ingredients where this bag is used in an efficient manner.
     * (Hopefully 1 query for all... not...)
     */
    public function getIngredientsWithRelations(): Collection
    {
        return $this->ingredients()
            ->with(['position.bottle'])
            ->get()
            ->sortBy(['position.bottle.date', 'position.variant.product.name']);
    }

    /**
     * Returns the relation with all the Ingredients where this bag is used
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * The date this bag was most recently drawn from into a bottling, or null
     * if it has never been used. Walks ingredients -> position -> bottle.
     */
    public function lastBottledAt(): ?Carbon
    {
        return $this->ingredients
            ->map(fn (Ingredient $i) => $i->position?->bottle?->date)
            ->filter()
            ->max();
    }

    /**
     * Returns the current amount used by compound products
     */
    public function getCompoundUsage(): float
    {
        $sum = 0;
        foreach ($this->ingredients as $i) {
            $variant = $i->position->variant;
            if (! $variant->product->type->compound) {
                continue;
            }
            foreach ($variant->product->herbs as $herb) {
                if ($herb->id == $this->herb->id) {
                    $sum += ($variant->size * $i->position->count) * ($herb->pivot->percentage / 100);
                }
            }
        }

        return $sum;
    }

    /**
     * Returns the current amount used by non-compound products
     */
    public function getNonCompoundUsage(): float
    {
        $sum = 0;
        foreach ($this->ingredients as $i) {
            $variant = $i->position->variant;
            if ($variant->product->type->compound) {
                continue;
            }
            foreach ($variant->product->herbs as $herb) {
                if ($herb->id == $this->herb->id) {
                    $sum += ($variant->size * $i->position->count) * ($herb->pivot->percentage / 100);
                }
            }
        }

        return $sum;
    }

    /**
     * Returns the current amount (With trashed) in percent
     */
    public function getCurrentPercentage(): float
    {
        return ($this->getCurrentWithTrashed() / $this->size) * 100;
    }

    /**
     * Returns the current amount in this bag in g,
     * taking also the trashed amount into account.
     */
    public function getCurrentWithTrashed(): float
    {
        return $this->getCurrent() - $this->trashed;
    }

    /**
     * Returns the size formatted in g
     */
    public function getSizeInGramm(): string
    {
        return sprintf('%ug', $this->size);
    }
}
