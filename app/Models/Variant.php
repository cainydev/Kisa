<?php

namespace App\Models;

use App\Facades\Billbee;
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

    public function sku(): Attribute
    {
        return new Attribute(get: fn() => $this->product->mainnumber . $this->ordernumber);
    }

    public function billbee(): Attribute
    {
        return new Attribute(get: function (): BillbeeProduct|null {
            $response = Billbee::products()->getProduct($this->sku, ProductLookupBy::SKU);
            return $response->getData();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bottles(): BelongsToMany
    {
        return $this->belongsToMany(Bottle::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(BottlePosition::class);
    }
}
