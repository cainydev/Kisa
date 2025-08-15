<?php

namespace App\Models;

use App\Facades\Billbee;
use App\Traits\CachedAttributes;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Product as BillbeeProduct;
use BillbeeDe\BillbeeAPI\Type\ProductLookupBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Variant extends Model
{
    use CachedAttributes;

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
            try {
                if ($billbeeProduct = $variant->fetchBillbeeProduct()) {
                    $this->stock = $billbeeProduct->stockCurrent;
                    $this->sku = $billbeeProduct->sku;
                    $this->ean = $billbeeProduct->ean;
                    $this->saveQuietly();
                }
            } catch (QuotaExceededException $e) {
                Log::warning("Billbee quota exceeded while initializing variant {$variant->id}: {$e->getMessage()}");
            } catch (\Throwable $e) {
                Log::error("Error initializing variant {$variant->id} with Billbee: {$e->getMessage()}");
            }
        });
    }

    /**
     * Try to find the billbee product.
     *
     * @throws QuotaExceededException
     */
    public function fetchBillbeeProduct(): ?BillbeeProduct
    {
        $billbeeProductData = null;

        if ($this->billbee_id) {
            try {
                $response = Billbee::products()->getProduct($this->billbee_id);
                if ($response->errorCode === 0 && $response->data !== null) {
                    $billbeeProductData = $response->data;
                }
            } catch (QuotaExceededException $e) {
                throw $e;
            } catch (\Throwable $e) {
                Log::warning("Error fetching Billbee product by ID {$this->billbee_id} for variant {$this->id}: {$e->getMessage()}");
            }
        }

        if ($billbeeProductData === null && $this->sku) {
            try {
                $response = Billbee::products()->getProduct($this->sku, ProductLookupBy::SKU);
                if ($response->errorCode === 0 && $response->data !== null) {
                    $billbeeProductData = $response->data;
                    $this->billbee_id = $billbeeProductData->id; // Set/update billbee_id
                    $this->saveQuietly();
                } else {
                    Log::info("Billbee product not found by SKU {$this->sku} for variant {$this->id}. Error code: {$response->errorCode}");
                    return null;
                }
            } catch (QuotaExceededException $e) {
                throw $e; // Re-throw
            } catch (\Throwable $e) {
                Log::error("Error fetching Billbee product by SKU {$this->sku} for variant {$this->id}: {$e->getMessage()}");
                return null;
            }
        }

        if ($billbeeProductData === null) {
            return null;
        }

        return $billbeeProductData;
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

    public function orderPositions(): HasMany
    {
        return $this->hasMany(OrderPosition::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_positions');
    }

    public function herbsNeededFor(int $amount): iterable
    {
        return $this->product
            ->recipeIngredients
            ->pluck('percentage', 'herb_id')
            ->map(fn($percentage) => $this->size * ($percentage / 100.0) * $amount);
    }

    public function name(): Attribute
    {
        return new Attribute(get: fn() => "{$this->product->name} {$this->size}g");
    }

    public function dailySales(): Attribute
    {
        return $this->cachedAttribute('daily', collect())();
    }

    public function weeklySales(): Attribute
    {
        return $this->cachedAttribute('weekly', collect())();
    }

    public function monthlySales(): Attribute
    {
        return $this->cachedAttribute('monthly', collect())();
    }

    public function yearlySales(): Attribute
    {
        return $this->cachedAttribute('yearly', collect())();
    }

    public function averageDailySales(): Attribute
    {
        return $this->cachedAttribute('daily:avg', 0.0)();
    }

    public function averageWeeklySales(): Attribute
    {
        return $this->cachedAttribute('weekly:avg', 0.0)();
    }

    public function averageMonthlySales(): Attribute
    {
        return $this->cachedAttribute('monthly:avg', 0.0)();
    }

    public function averageYearlySales(): Attribute
    {
        return $this->cachedAttribute('yearly:avg', 0.0)();
    }

    public function depletedDate(): Attribute
    {
        return $this->cachedAttribute('depleted', Carbon::endOfTime())();
    }

    public function nextSale(): Attribute
    {
        return $this->cachedAttribute('next_sale', Carbon::endOfTime())();
    }
}
