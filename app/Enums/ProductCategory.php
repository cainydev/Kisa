<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Product categories (Erzeugniskategorien) as defined by the EU organic
 * regulation and printed on an organic certificate. A certificate carries one
 * or more of these. Backing values are the regulation's category letters
 * (a–g), which are the stable canonical identifiers.
 */
enum ProductCategory: string implements HasLabel
{
    case UnprocessedPlants = 'a';
    case LiveAnimals = 'b';
    case Aquaculture = 'c';
    case ProcessedFood = 'd';
    case Feed = 'e';
    case Wine = 'f';
    case Other = 'g';

    public function getLabel(): string
    {
        return match ($this) {
            self::UnprocessedPlants => 'a) unverarbeitete Pflanzen und pflanzliche Erzeugnisse',
            self::LiveAnimals => 'b) lebende Tiere und unverarbeitete tierische Erzeugnisse',
            self::Aquaculture => 'c) Algen und unverarbeitete Erzeugnisse der Aquakultur',
            self::ProcessedFood => 'd) verarbeitete landwirtschaftliche Erzeugnisse (Lebensmittel)',
            self::Feed => 'e) Futtermittel',
            self::Wine => 'f) Wein',
            self::Other => 'g) sonstige Erzeugnisse',
        };
    }
}
