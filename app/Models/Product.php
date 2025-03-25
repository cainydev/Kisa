<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a final product.
 * Has many variants, a product type and recipe ingredients.
 */
class Product extends Model
{
    protected $guarded = [];

    /**
     * Returns the percentage of a herb in the recipe
     *
     * @param Herb $herb The herb to check
     * @return float The percentage amount
     */
    public function getPercentage(Herb $herb): float
    {
        if ($this->herbs->contains($herb)) {
            return $this->herbs->find($herb)->pivot->percentage;
        } else {
            return 0.0;
        }
    }

    /**
     * The recipe ingredients
     *
     * @return HasMany
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * The type of product
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * The variants of the product.
     *
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function herbs(): BelongsToMany
    {
        return $this->belongsToMany(Herb::class, 'herb_product')
            ->withPivot('percentage');
    }
}
