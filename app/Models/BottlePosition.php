<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

use App\Models\Ingredient;
use Orchid\Screen\AsSource;

class BottlePosition extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];

    protected $with = ['variant'];

    public function bottle()
    {
        return $this->belongsTo(Bottle::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Returns the K&W Charge of the bottle position.
     * Returns a generated charge if has multiple or none ingredients
     * Returns the supplier charge if has exactly one ingredient
     * @return string
     */
    public function getCharge(): string
    {
        $herbsContained = $this->variant->product->herbs;
        if ($herbsContained->count() == 1) {
            if ($this->ingredients->count() == 1) {
                return $this->ingredients->first()->bag->charge;
            }
        } else {
            $bottlePositionsInThisMonth =
                BottlePosition::all()
                ->whereBetween('bottle.date', [$this->bottle->date->startOfMonth(), $this->bottle->date->endOfMonth()]);

            $index = 1;
            foreach ($bottlePositionsInThisMonth as $pos) {
                if ($this->id == $pos->id) {
                    return $this->bottle->date->format('ymd') . $index;
                }
                $index++;
            }
        }
        return 'CHARGE_NOT_CALCULATABLE';
    }

    public function hasBagFor(Herb $herb)
    {
        $i = $this->ingredients->where('herb_id', $herb->id)->first();

        if ($i != null) return true;

        return false;
    }

    public function isBagFor(Bag $bag, Herb $herb)
    {
        $i = $this->ingredients->where('herb_id', $herb->id)->first();
        if ($i == null) return false;
        return $i->bag->id == $bag->id;
    }

    public function hasAllBags()
    {
        foreach ($this->variant->product->herbs as $herb) {
            if (!$this->hasBagFor($herb)) return false;
        }
        return true;
    }

    protected static function booted()
    {
        static::created(function ($pos) {
            $pos->charge = $pos->getCharge();
            $pos->save();
        });
    }
}
