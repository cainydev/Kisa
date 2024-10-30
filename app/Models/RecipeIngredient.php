<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RecipeIngredient extends Pivot
{
    protected $table = "herb_product";

    /**
     * The related product for this recipe ingredient
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The ingredient part
     * @return BelongsTo
     */
    public function herb(): BelongsTo
    {
        return $this->belongsTo(Herb::class);
    }
}
