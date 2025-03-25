<?php

namespace App\Models;

use App\Facades\Billbee;
use BillbeeDe\BillbeeAPI\Model\Product as BillbeeProduct;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variant extends Model
{
    protected $guarded = [];

    /**
     * Always queries the related product
     * @var string[]
     */
    protected $with = ['product'];

    /**
     * Initializes the variant with billbee data
     *
     * @return void
     */
    public static function booted(): void
    {
        static::creating(function (Variant $variant) {
            if (!empty($variant->billbee_id) && !empty($variant->ean)) return;

            $billbee = $variant->billbee;
            if ($billbee === null) return;

            if (empty($variant->billbee_id)) $variant->billbee_id = $billbee->id;
            if (empty($variant->ean)) $variant->ean = $billbee->ean;
            if (empty($variant->stock)) $variant->stock = $billbee->stockCurrent;
        });
    }

    /**
     * The billbee product data. Expensive operation.
     *
     * @return Attribute
     */
    public function billbee(): Attribute
    {
        return Attribute::make(get: function (?string $value, array $attributes): BillbeeProduct|null {
            if (empty($attributes['billbee_id'])) return null;
            return Billbee::products()->getProduct($attributes['billbee_id'])->data;
        });
    }

    /**
     * The product the variant belongs to
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The bottles that contain this variant
     *
     * @return BelongsToMany
     */
    public function bottles(): BelongsToMany
    {
        return $this->belongsToMany(Bottle::class);
    }


    /**
     * The positions that contain this variant
     *
     * @return HasMany
     */
    public function positions(): HasMany
    {
        return $this->hasMany(BottlePosition::class);
    }


}
