<?php

namespace App\Models;

use App\Facades\Billbee;
use App\Traits\CachedAttributes;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
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
                $variant->fetchBillbee();
            } catch (QuotaExceededException $e) {
                Log::warning("Billbee quota exceeded while initializing variant {$variant->id}: {$e->getMessage()}");
            } catch (\Throwable $e) {
                Log::error("Error initializing variant {$variant->id} with Billbee: {$e->getMessage()}");
            }
        });
    }

    /**
     * Try to find the billbee product data and set billbee_id.
     * This also updates the local EAN.
     * The initial stock setting via $this->stock will be handled by the stock attribute's logic.
     *
     * @return bool If the operation was successful
     * @throws QuotaExceededException
     */
    public function fetchBillbee(): bool
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
                } else {
                    Log::info("Billbee product not found by SKU {$this->sku} for variant {$this->id}. Error code: {$response->errorCode}");
                    return false;
                }
            } catch (QuotaExceededException $e) {
                throw $e; // Re-throw
            } catch (\Throwable $e) {
                Log::error("Error fetching Billbee product by SKU {$this->sku} for variant {$this->id}: {$e->getMessage()}");
                return false;
            }
        }

        if ($billbeeProductData === null) {
            return false;
        }

        $this->ean = $billbeeProductData->ean;
        $this->stock = $billbeeProductData->stockCurrent ?? 0;
        $this->billbee = $billbeeProductData;

        $this->save();

        return true;
    }

    /**
     * The billbee product data. Expensive operation. Fetches if necessary.
     *
     * @return Attribute
     */
    public function billbee(): Attribute
    {
        return $this->cachedAttribute('billbee', fn() => $this->fetchBillbee() ? $this->billbee : null)();
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

    public function stock(): Attribute
    {
        return $this->cachedAttribute('stock', 0)();
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
}
