<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;


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
     * Uploads this position to Billbee, therefore increasing the Stock in Billbee for this product.
     */
    public function upload(){
        $this->variant->getStockFromBillbee();

        $user = env('BILLBEE_USER');
        $pw = env('BILLBEE_PW');
        $key = env('BILLBEE_KEY');
        $host = env('BILLBEE_HOST');

        $body = [
            "Sku" => $this->variant->getSKU(),
            "Reason" => "Einlagerung " . $this->charge,
            "OldQuantity" => $this->variant->stock,
            "NewQuantity" => $this->variant->stock + $this->count,
            "DeltaQuantity" => $this->count
        ];

        $response = Http::acceptJson()
            ->withBasicAuth($user, $pw)
            ->withHeaders(['X-Billbee-Api-Key' => $key])
            ->withBody(json_encode($body), 'application/json')
            ->retry(2, 500)
            ->post($host . 'products/updatestock');

        if($response->successful()){
            $this->update(['uploaded' => true]);
            return true;
        }

        return false;
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
            $bottlePositionsToday =
                BottlePosition::all()
                ->where('bottle.date', $this->bottle->date)
                ->where(function($pos) {
                    return $pos->variant->product->herbs->count() > 1;
                });

            $index = 1;
            foreach ($bottlePositionsToday as $pos) {
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

        if($i->bag == null){
            $i->delete();
            return false;
        }
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
