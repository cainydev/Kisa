<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $guarded = [];

    public function bioInspector(): BelongsTo
    {
        return $this->belongsTo(BioInspector::class);
    }

    public function herbs(): HasMany
    {
        return $this->hasMany(Herb::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
