<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class Bottle extends Model
{
    use HasFactory, AsSource;

    protected $casts = [
        'date' => 'datetime:d.m.Y',
    ];

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function positions()
    {
        return $this->hasMany(BottlePosition::class);
    }

    public function finished(){
        foreach($this->positions as $pos){
            if(!$pos->hasAllBags()) return false;
        }
        return true;
    }
}
