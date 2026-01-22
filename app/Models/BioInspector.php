<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BioInspector extends Model
{
    protected $guarded = [];

    /**
     * Returns all suppliers supervised by the bioInspector
     * @return HasMany The relationship
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }
}
