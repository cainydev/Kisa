<?php

namespace App\Models;

use App\Facades\Billbee;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Product as BillbeeProduct;
use BillbeeDe\BillbeeAPI\Type\ProductLookupBy;
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
        static::created(function (Variant $variant) {
            $this->hasBillbee();
        });
    }

    /**
     * Try to find the billbee product data and set billbee_id
     *
     * @return bool If the operation was successful
     * @throws QuotaExceededException
     */
    public function hasBillbee(): bool
    {
        $billbee = $this->billbee;

        if ($billbee === null) {
            $response = Billbee::products()->getProduct($this->sku, ProductLookupBy::SKU);
            if ($response->errorCode !== 0 || $response->data === null) return false;

            $billbee = $response->data;
            $this->billbee_id = $billbee->id;
        }

        if ($billbee === null) return false;

        $this->ean = $billbee->ean;
        $this->stock = $billbee->stockCurrent;
        $this->save();

        return true;
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
