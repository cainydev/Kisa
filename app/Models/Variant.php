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

    public function billbee(): Attribute
    {
        return Attribute::make(get: function (): BillbeeProduct|null {
            return Billbee::products()->getProduct($this->billbeeId)->data;
        });
    }

    public function billbeeId(): Attribute
    {
        if ($this->billbee_id === null || $this->billbee_id === '') {
            $billbee = Billbee::products()->getProduct($this->ordernumber, ProductLookupBy::SKU)->data;
            if ($billbee !== null) {
                $this->billbee_id = $billbee->id;
                $this->save();
            }

        }

        return Attribute::make(get: function (?string $value, array $attributes): string {
            if ($value === null || $value === '') {
                $billbee = Billbee::products()->getProduct($attributes['ordernumber'], ProductLookupBy::SKU)->data;
                if ($billbee !== null) {
                    $this->billbee_id = $billbee->id;
                    $this->save();
                }
            }

            return $this->billbee_id;
        });
    }

    public function ean(): Attribute
    {
        return Attribute::make(get: function (?string $value, array $attributes): string {
            if ($value === null || $value === '') {
                $billbee = $this->billbee;
                if ($billbee !== null) {
                    $this->ean = $billbee->ean;
                    $this->save();
                }
            }

            return $this->ean;
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
