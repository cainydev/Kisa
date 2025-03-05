<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends Model
{
    protected $guarded = [];

    public function position(): BelongsTo
    {
        return $this->belongsTo(BottlePosition::class, 'bottle_position_id');
    }

    public function herb(): BelongsTo
    {
        return $this->belongsTo(Herb::class);
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(Bag::class);
    }
}
