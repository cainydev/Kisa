<?php

namespace App\Models;

use App\Orchid\Presenters\HerbPresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Orchid\Screen\AsSource;

class Herb extends Model
{
    use HasFactory, AsSource, Searchable;

    protected $guarded = [];

    public function presenter()
    {
        return new HerbPresenter($this);
    }

    public function toSearchableArray()
    {
        $prods = '';
        foreach ($this->products as $prod) {
            $prods .= $prod->name . ', ';
        }
        $prods = substr($prods, 0, strlen($prods) - 2);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullname' => $this->fullname,
            'prods' => $prods,
        ];
    }

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
