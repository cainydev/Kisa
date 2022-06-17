<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function herb()
    {
        return $this->belongsTo(Herb::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function getCurrent(){
        $sum = 0;
        foreach($this->ingredients as $i){
            $variant = $i->position->variant;
            foreach($variant->product->herbs as $herb){
                if($herb->id == $this->herb->id){
                    $sum += ($variant->size * $i->position->count) * ($herb->pivot->percentage / 100);
                }
            }
        }
        return $this->size - $sum;
    }

    public function getCurrentWithTrashed(){
        return $this->getCurrent() - $this->trashed;
    }

    public function getCurrentPercentage(){
        return ($this->getCurrentWithTrashed() / $this->size) * 100;
    }

    public function ingredients(){
        return $this->hasMany(Ingredient::class);
    }

    public function getSizeInKilo(){
        return sprintf("%.1fkg", $this->size / 1000);
    }

    public function getSizeInGramm(){
        return sprintf("%ug", $this->size);
    }
}
