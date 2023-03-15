<?php

namespace App\Models;

use App\Orchid\Presenters\HerbPresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
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

    public function setRedisAveragePerDay(float $value)
    {
        return Redis::set('herb:' . $this->herb->id . ':per.day', $value);
    }

    public function getRedisAveragePerDay()
    {
        return Redis::get('herb:' . $this->herb->id . ':per.day');
    }

    public function setRedisAveragePerMonth(float $value)
    {
        return Redis::set('herb:' . $this->herb->id . ':per.month', $value);
    }

    public function getRedisAveragePerMonth()
    {
        return Redis::get('herb:' . $this->herb->id . ':per.month');
    }

    public function setRedisAveragePerYear(float $value)
    {
        return Redis::set('herb:' . $this->herb->id . ':per.year', $value);
    }

    public function getRedisAveragePerYear()
    {
        return Redis::get('herb:' . $this->herb->id . ':per.year');
    }

    public function setRedisBought(float $value)
    {
        return Redis::set('herb:' . $this->herb->id . ':bought', $value);
    }

    public function getRedisBought()
    {
        return Redis::get('herb:' . $this->herb->id . ':bought');
    }

    public function setRedisUsed(float $value)
    {
        return Redis::set('herb:' . $this->herb->id . ':used', $value);
    }

    public function getRedisUsed()
    {
        return Redis::get('herb:' . $this->herb->id . ':used');
    }

    public function setRedisRemaining(float $value)
    {
        return Redis::set('herb:' . $this->herb->id . ':remaining', $value);
    }

    public function getRedisRemaining()
    {
        return Redis::get('herb:' . $this->herb->id . ':remaining');
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
