<?php

namespace App\Models;

use App\Orchid\Presenters\BottlePresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Orchid\Screen\AsSource;

class Bottle extends Model
{
    use HasFactory, AsSource, Searchable;

    protected $casts = [
        'date' => 'datetime:d.m.Y',
    ];

    protected $guarded = [];

    public function presenter()
    {
        return new BottlePresenter($this);
    }

    public function toSearchableArray()
    {
        $prods = '';
        foreach ($this->positions as $pos) {
            $prods .= $pos->variant->product->name . ', ';
        }
        $prods = substr($prods, 0, strlen($prods) - 2);

        return [
            'id' => $this->id,
            'note' => $this->note,
            'prods' => $prods,
            'date' => $this->date->format('d.m.Y') . ' or ' . $this->date->format('d.m.y')
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function positions()
    {
        return $this->hasMany(BottlePosition::class);
    }

    public function finished()
    {
        foreach ($this->positions as $pos) {
            if (!$pos->hasAllBags()) return false;
        }
        return true;
    }
}
