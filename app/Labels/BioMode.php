<?php

namespace App\Labels;

use App\Models\Herb;

enum BioMode: string
{
    /** No bio claim — every ingredient is non-bio. */
    case None = 'none';

    /** All ingredients are certified bio (asterisk + footnote, EU leaf, DE-ÖKO-039). */
    case Bio = 'bio';

    /**
     * Derive bio status per herb at render time by inspecting the herb's
     * current bags (ones with at least the configured remaining grams).
     */
    case FromStock = 'from_stock';

    /** Threshold (in grams) below which a bag is ignored when checking stock. */
    public const STOCK_MIN_REMAINING_GRAMS = 100.0;

    /**
     * Whether the given herb counts as bio under this mode. For the
     * `from_stock` mode all currently-stocked bags must be bio for the herb
     * to be considered bio (conservative — a single non-bio bag flips it).
     */
    public function herbIsBio(Herb $herb): bool
    {
        return match ($this) {
            self::None => false,
            self::Bio => true,
            self::FromStock => self::stockIsAllBio($herb),
        };
    }

    /**
     * @return array<string, string> Map of value => human label, for the UI dropdown.
     */
    public static function options(): array
    {
        return [
            self::None->value => 'Nicht Bio',
            self::FromStock->value => 'Bestand prüfen',
            self::Bio->value => 'Bio',
        ];
    }

    private static function stockIsAllBio(Herb $herb): bool
    {
        $bags = $herb->bags()->get();
        $relevant = $bags->filter(fn ($bag) => $bag->getCurrent() >= self::STOCK_MIN_REMAINING_GRAMS);
        if ($relevant->isEmpty()) {
            return false;
        }

        return $relevant->every(fn ($bag) => (bool) $bag->bio);
    }
}
