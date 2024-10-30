<?php

namespace App\Models;

use App\Jobs\AnalyzeHerb;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class Bag extends Model
{
    protected $guarded = [];

    protected $with = ['herb'];

    protected $casts = [
        'bestbefore' => 'date:Y-m-d',
        'steamed' => 'date:Y-m-d',
    ];

    /**
     * Lifecycle hooks
     * @return void
     */
    protected static function booted(): void
    {
        static::updated(function (Bag $bag) {
            AnalyzeHerb::dispatch($bag->herb);
        });
    }

    /**
     * Get the computed identifier of this bag.
     * @return Attribute
     */
    public function identifier(): Attribute
    {
        return new Attribute(get: function () {
            return $this->specification . ' ' . $this->herb->fullname . $this->getSizeInKilo();
        });
    }

    /**
     * @return mixed
     */
    public function redisCurrent(): Attribute
    {
        $redisQuery = "bag:$this->id:remaining";

        return new Attribute(
            get: fn () => floatval(Redis::get($redisQuery)),
            set: fn ($value) => Redis::set($redisQuery, $value) && $value
        );
    }

    /**
     * Returns the herb that this bag contains
     *
     * @return BelongsTo
     */
    public function herb(): BelongsTo
    {
        return $this->belongsTo(Herb::class);
    }

    /**
     * Returns the delivery this bag was delivered in
     *
     * @return BelongsTo
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Returns the relation with all the Ingredients where this bag is used
     *
     * @return HasMany
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Returns all the Ingredients where this bag is used in an efficient manner.
     * (Hopefully 1 query for all.. not..)
     *
     * @return Collection
     */
    public function getIngredientsWithRelations(): Collection
    {
        return $this->ingredients()
            ->with(['position.bottle'])
            ->get()
            ->sortBy(['position.bottle.date', 'position.variant.product.name']);
    }

    /**
     * Returns the current amount in this bag in g
     *
     * @return float
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
     * Returns the current amount used by compound products
     *
     * @return float
     */
    public function getCompoundUsage(): float
    {
        $sum = 0;
        foreach ($this->ingredients as $i) {
            $variant = $i->position->variant;
            if (!$variant->product->type->compound) {
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
     *
     * @return float
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
     * Returns the current amount in this bag in g,
     * taking also the trashed amount into account.
     *
     * @return float
     */
    public function getCurrentWithTrashed(): float
    {
        return $this->getCurrent() - $this->trashed;
    }

    /**
     * Returns the current amount (With trashed) in percent
     *
     * @return float
     */
    public function getCurrentPercentage(): float
    {
        return ($this->getCurrentWithTrashed() / $this->size) * 100;
    }

    /**
     * Returns the size formatted in kg
     *
     * @return string
     */
    public function getSizeInKilo(): string
    {
        return sprintf('%.1fkg', $this->size / 1000);
    }

    /**
     * Returns the size formatted in g
     *
     * @return string
     */
    public function getSizeInGramm(): string
    {
        return sprintf('%ug', $this->size);
    }
}
