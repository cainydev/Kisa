<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function hasBagFor(Herb $herb){
        $i = $this->ingredients->where('herb_id', $herb->id)->first();

        if($i != null) return true;

        return false;
    }

    public function isBagFor(Bag $bag, Herb $herb){
        $i = $this->ingredients->where('herb_id', $herb->id)->first();
        if($i == null) return false;
        return $i->bag->id == $bag->id;
    }

    public function hasAllBags(){
        foreach($this->variant->product->herbs as $herb){
            if(!$this->hasBagFor($herb)) return false;
        }
        return true;
    }

}
