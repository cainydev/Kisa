<?php

namespace App\Models;

use App\Facades\Billbee;
use BillbeeDe\BillbeeAPI\Model\Stock;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected $guarded = [];

    /**
     * Always queries the related product variant
     * @var string[]
     */
    protected $with = ['variant'];

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

    /**
     * Returns the K&W Charge of the bottle position.
     * Case 1) Returns a generated charge if it has multiple or none ingredients
     * Case 2) Returns the supplier charge if it has exactly one ingredient
     * @return string|null The generated charge number
     */
    public function getCharge(): ?string
    {
        $ingredients = $this->variant->product->recipeIngredients;

        if ($ingredients->count() === 1) {
            if ($this->ingredients->isEmpty()) return null;
            return $this->ingredients->first()->bag->charge;
        } else {
            $bottlePositionsToday =
                BottlePosition::all()
                    ->where('bottle.date', $this->bottle->date)
                    ->where('created_at', '<', $this->created_at)
                    ->filter(function ($pos) {
                        return $pos->variant->product->recipeIngredients->count() > 1;
                    })
                    ->count();

            return $this->bottle->date->format('ymd') . $bottlePositionsToday + 1;
        }
    }

    /**
     * Update the stock for this variant in Billbee
     * @return bool True, if stock is updated
     */
    public function upload(): bool
    {
        if ($this->uploaded) return true;

        if (!$this->hasAllBags()) {
            Notification::make()
                ->warning()
                ->title('Einlagern fehlgeschlagen')
                ->body('Es sind nicht alle verwendeten Rohstoffe zugewiesen.')
                ->send();

            return false;
        }

        if (empty($this->charge)) {
            $this->charge = $this->getCharge();
            $this->save();
        }

        if (empty($this->charge) || $this->charge === 'CHARGE_NOT_CALCULATABLE') {
            Notification::make()
                ->danger()
                ->title('Einlagern fehlgeschlagen')
                ->body('Die Charge wurde nicht angegeben und konnte nicht berechnet werden.')
                ->send();

            return false;
        }

        try {
            $newStock = Stock::fromProduct($this->variant->billbee)
                ->setDeltaQuantity($this->count)
                ->setReason("Einlagerung $this->charge");

            Billbee::products()->updateStock($newStock);

            $this->variant->update([
                'stock' => $newStock->getNewQuantity()
            ]);

            $this->update([
                'uploaded' => true
            ]);

            Notification::make()
                ->title('Einlagern erfolgreich')
                ->body('Neuer Bestand in Billbee: ' . $newStock->getNewQuantity())
                ->success()
                ->send();

            return true;
        } catch (Exception $e) {
            Notification::make()
                ->title('Einlagern fehlgeschlagen')
                ->body('Bitte warte etwas zwischen deinen Anfragen.')
                ->danger()
                ->send();

            return false;
        }
    }

    public function hasAllBags(): bool
    {
        $herbsSpecified = $this->ingredients->pluck('herb_id');
        $herbsRequired = $this->variant->product->recipeIngredients->pluck('herb_id');

        return $herbsRequired->diff($herbsSpecified)->isEmpty();
    }

    public function hasBagFor(Herb $herb): bool
    {
        return $this->getBagFor($herb) !== null;
    }

    public function getBagFor(Herb $herb): Bag|null
    {
        return $this->ingredients
            ->firstWhere('herb_id', $herb->id)
            ?->bag()
            ->withTrashed()
            ->first();
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
}
