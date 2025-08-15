<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    /**
     * The positions of this order.
     * Only includes positions with variant that are tracked by this system.
     *
     * @return HasMany
     */
    public function positions(): HasMany
    {
        return $this->hasMany(OrderPosition::class);
    }
}
