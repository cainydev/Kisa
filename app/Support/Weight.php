<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Formats gram-based weights for display. Grams are the app's canonical unit;
 * these helpers render them as localized "kg"/"g" strings so call sites don't
 * re-derive the `/ 1000` conversion and locale formatting everywhere.
 *
 * Exposed as the `Number::kilos()` / `Number::grams()` macros (registered in
 * AppServiceProvider) so it reads naturally alongside Laravel's Number helper.
 */
class Weight
{
    public static function kilos(int|float $grams, int $precision = 2): string
    {
        return Number::format($grams / 1000, precision: $precision).' kg';
    }

    public static function grams(int|float $grams, int $precision = 0): string
    {
        return Number::format($grams, precision: $precision).' g';
    }
}
