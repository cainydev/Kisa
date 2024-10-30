<?php

namespace App\Models;

use App\Facades\Billbee;
use BillbeeDe\BillbeeAPI\Model\Stock;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A position in a bottle process.
 * References the product variant being bottled.
 * Has a count indicating the quantity of the variant.
 * Has many ingredients that were supplied to satisfy this position.
 */
class BottlePosition extends Model
{
    protected $guarded = [];

    /**
     * Always queries the related product variant
     * @var string[]
     */
    protected $with = ['variant'];

    /**
     * Update the stock for this variant in Billbee
     * @return bool True, if stock is updated
     */
    public function upload(): bool
    {
        if($this->uploaded) return true;

        try {
            $newStock = Stock::fromProduct($this->variant->billbee)
                ->setDeltaQuantity($this->count)
                ->setReason("Einlagerung $this->charge");

            Billbee::products()->updateStock($newStock);

            $this->uploaded = true;
            $this->save();

            return true;
        } catch (Exception $e) {
            Notification::make()
                ->title('Billbee ist überfordert!')
                ->body('Bitte warte etwas zwischen deinen Anfragen.')
                ->danger()
                ->send();

            return false;
        }
    }

    /**
     * Returns the K&W Charge of the bottle position.
     * Case 1) Returns a generated charge if has multiple or none ingredients
     * Case 2) Returns the supplier charge if has exactly one ingredient
     * @return string The generated charge number
     */
    public function getCharge(): string
    {
        $herbsContained = $this->variant->product->herbs;
        if ($herbsContained->count() == 1) {
            if ($this->ingredients->count() == 1) {
                return $this->ingredients->first()->bag->charge;
            }
        } else {
            $bottlePositionsToday =
                BottlePosition::all()
                ->where('bottle.date', $this->bottle->date)
                ->where(function ($pos) {
                    return $pos->variant->product->herbs->count() > 1;
                });

            $index = 1;
            foreach ($bottlePositionsToday as $pos) {
                if ($this->id == $pos->id) {
                    return $this->bottle->date->format('ymd').$index;
                }
                $index++;
            }
        }

        return 'CHARGE_NOT_CALCULATABLE';
    }

    public function hasBagFor(Herb $herb): bool
    {
        $i = $this->ingredients->where('herb_id', $herb->id)->first();

        if ($i != null) {
            return true;
        }

        return false;
    }

    public function isBagFor(Bag $bag, Herb $herb): bool
    {
        $i = $this->ingredients->where('herb_id', $herb->id)->first();
        if ($i == null) {
            return false;
        }

        if ($i->bag == null) {
            $i->delete();

            return false;
        }

        return $i->bag->id == $bag->id;
    }

    public function hasAllBags(): bool
    {
        foreach ($this->variant->product->herbs as $herb) {
            if (!$this->hasBagFor($herb)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The related bottle process
     * @return BelongsTo
     */
    public function bottle(): BelongsTo
    {
        return $this->belongsTo(Bottle::class);
    }

    /**
     * The related product variant
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * The ingredients used in the process of completing this position
     * @return HasMany
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Initializes the position with a auto-generated charge
     */
    protected static function booted(): void
    {
        static::created(function (self $pos) {
            $pos->charge = $pos->getCharge();
            $pos->save();
        });
    }
}
