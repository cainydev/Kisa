<?php

namespace App\Models;

use App\Orchid\Presenters\BagPresenter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Redis;
use Laravel\Scout\Searchable;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;

class Bag extends Model
{
    use HasFactory, AsSource, Filterable, Searchable;

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


    public function getRedisCurrent()
    {
        return Redis::get('bag:' . $this->id . ':remaining');
    }

    public function setRedisCurrent(float $value)
    {
        return Redis::set('bag:' . $this->id . ':remaining', $value);
    }

    public function presenter()
    {
        return new BagPresenter($this);
    }

    public function toSearchableArray()
    {
        $bottles = '';
        foreach ($this->ingredients as $ing) {
            $bottles .= 'Abfüllung vom ' . $ing->position->bottle->date->format('d.m.Y') . ', ';
        }
        $bottles = substr($bottles, 0, strlen($bottles) - 2);

        return [
            'id' => $this->id,
            'charge' => $this->charge,
            'size' => $this->size,
            'herb' => $this->herb->fullname,
            'date' => $this->delivery->delivered_date->format('d.m.Y') . ' or ' . $this->delivery->delivered_date->format('d.m.y'),
            'specification' => $this->specification,
            'bottles' => $bottles,
        ];
    }

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
        ])->get()->sortBy(['position.bottle.date', 'position.variant.product.name']);
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
     * Returns the current amount used by compound products
     * @return void
     */
    public function getCompoundUsage()
    {
        $sum = 0;
        foreach ($this->ingredients as $i) {
            $variant = $i->position->variant;
            if (!$variant->product->type->compound) continue;
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
     * @return void
     */
    public function getNonCompoundUsage()

    {
        $sum = 0;
        foreach ($this->ingredients as $i) {
            $variant = $i->position->variant;
            if ($variant->product->type->compound) continue;
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
