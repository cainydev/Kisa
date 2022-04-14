<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class Variant extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];

    protected $with = ['product'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bottles()
    {
        return $this->belongsToMany(Bottle::class);
    }

    public function positions(){
        return $this->hasMany(BottlePosition::class);
    }
}
