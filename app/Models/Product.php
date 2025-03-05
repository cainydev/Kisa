<?php

namespace App\Models;

use App\Facades\Billbee;
use BillbeeDe\BillbeeAPI\Model\Product as BillbeeProduct;
use BillbeeDe\BillbeeAPI\Type\ProductLookupBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a final product.
 * Has many variants, a product type and a recipe.
 */
class Product extends Model
{
    protected $guarded = [];

    /**
     * Get the billbee product instance
     * @return Attribute
     */
    public function billbee(): Attribute
    {
        return new Attribute(get: function (): BillbeeProduct|null {
            return Billbee::products()
                ->getProduct($this->mainnumber, ProductLookupBy::SKU)
                ->getData();
        });
    }

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
     * @return HasMany
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * The type of product
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * The variants of the product.
     * The mainnumber concatenated with the ordernumber of the variant forms the SKU of the variant.
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
