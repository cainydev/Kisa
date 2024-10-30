<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $guarded = [];

    public function position()
    {
        return $this->belongsTo(BottlePosition::class, 'bottle_position_id');
    }

    public function herb()
    {
        return $this->belongsTo(Herb::class);
    }

    public function bag()
    {
        return $this->belongsTo(Bag::class);
    }
}
