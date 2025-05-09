<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Redis;

class Herb extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function toSearchableArray(): array
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
        return Redis::set('herb:' . $this->id . ':per.day', $value);
    }

    public function getRedisAveragePerDay()
    {
        return Redis::get('herb:' . $this->id . ':per.day');
    }

    public function setRedisAveragePerMonth(float $value)
    {
        return Redis::set('herb:' . $this->id . ':per.month', $value);
    }

    public function getRedisAveragePerMonth()
    {
        return Redis::get('herb:' . $this->id . ':per.month');
    }

    public function setRedisAveragePerYear(float $value)
    {
        return Redis::set('herb:' . $this->id . ':per.year', $value);
    }

    public function getRedisAveragePerYear()
    {
        return Redis::get('herb:' . $this->id . ':per.year');
    }

    public function setRedisBought(float $value)
    {
        return Redis::set('herb:' . $this->id . ':bought', $value);
    }

    public function getRedisBought()
    {
        return Redis::get('herb:' . $this->id . ':bought');
    }

    public function setRedisUsed(float $value)
    {
        return Redis::set('herb:' . $this->id . ':used', $value);
    }

    public function getRedisUsed()
    {
        return Redis::get('herb:' . $this->id . ':used');
    }

    public function setRedisGrammRemaining(float $value)
    {
        return Redis::set('herb:' . $this->id . ':gramm.remaining', $value);
    }

    public function getRedisGrammRemaining()
    {
        return Redis::get('herb:' . $this->id . ':gramm.remaining');
    }

    public function setRedisDaysRemaining(float $value)
    {
        return Redis::set('herb:' . $this->id . ':days.remaining', $value);
    }

    public function getRedisDaysRemaining()
    {
        return Redis::get('herb:' . $this->id . ':days.remaining');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Bag::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
