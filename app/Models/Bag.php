<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;

class Bag extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $guarded = [];

    protected $with = ['herb'];

    protected $casts = [
        'bestbefore' => 'date:Y-m-d',
        'steamed' => 'date:Y-m-d',
    ];

    protected $allowedFilters = [
        'id',
        'charge',
        'bio',
        'size',
        'specification',
        'trashed',
    ];

    protected $allowedSorts = [
        'id',
        'charge',
        'bio',
        'size',
        'bestbefore',
        'specification',
        'trashed',
    ];

    /**
     * Returns the herb that this bag contains
     * @return BelongsTo
     */
    public function herb()
    {
        return $this->belongsTo(Herb::class);
    }

    /**
     * Returns the delivery this bag was delivered in
     * @return BelongsTo
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Returns the relation with all the Ingredients where this bag is used
     * @return HasMany
     */
    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Returns all the Ingredients where this bag is used in an efficient manner.
     * (Hopefully 1 query for all.. not..)
     * @return Collection
     */
    public function getIngredientsWithRelations()
    {
        return $this->ingredients()->with([
            'position' => [
                'bottle',
            ]
        ])->get();
    }

    /**
     * Returns the current amount in this bag in g
     * @return int|float
     */
    public function getCurrent()
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
     * Returns the current amount in this bag in g,
     * taking also the trashed amount into account.
     * @return int|float
     */
    public function getCurrentWithTrashed()
    {
        return $this->getCurrent() - $this->trashed;
    }

    /**
     * Returns the current amount (With trashed) in percent
     * @return int|float
     */
    public function getCurrentPercentage()
    {
        return ($this->getCurrentWithTrashed() / $this->size) * 100;
    }

    /**
     * Returns the size formatted in kg
     * @return string
     */
    public function getSizeInKilo()
    {
        return sprintf("%.1fkg", $this->size / 1000);
    }

    /**
     * Returns the size formatted in g
     * @return string
     */
    public function getSizeInGramm()
    {
        return sprintf("%ug", $this->size);
    }
}
