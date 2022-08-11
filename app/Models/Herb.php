<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class Herb extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];


    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function bags()
    {
        return $this->hasMany(Bag::class);
    }

    public function standardSupplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
