<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class ProductType extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
