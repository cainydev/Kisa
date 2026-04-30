<?php

namespace App\Labels;

use App\Models\Product;

/**
 * Renders a product's recipe as an ingredient list for label backs.
 *
 * Three rendering shapes, picked by the active bio mode AND whether the
 * resulting list is fully or partially bio:
 *
 *  - none           plain name, no asterisk, no percentage
 *  - all bio        plain name, no asterisk; a single closing sentence
 *                   ("aus kontrolliert biologischem Anbau …") applies to
 *                   the whole list and is appended inline by the blade
 *  - partially bio  bio entries get `name*`, non-bio entries get
 *                   `{percentage}% name`; a `*…` footnote line is
 *                   rendered separately
 *
 * Two passes: classify every herb, then format. We can only know whether
 * to emit asterisks on bio entries after we've checked if any non-bio
 * entries exist.
 */
class IngredientList
{
    /**
     * @param  string  $text  Comma-separated ingredient list, ready to print.
     * @param  bool  $anyBio  At least one ingredient is bio.
     * @param  bool  $allBio  Every ingredient is bio.
     * @param  float  $nonBioPercent  Sum of non-bio ingredient percentages.
     */
    public function __construct(
        public readonly string $text,
        public readonly bool $anyBio,
        public readonly bool $allBio,
        public readonly float $nonBioPercent,
    ) {}

    public static function build(?Product $product, BioMode $mode): self
    {
        if (! $product) {
            return new self('', false, false, 0.0);
        }
        $herbs = $product->herbs()->orderByPivot('percentage', 'desc')->orderBy('name')->get();
        if ($herbs->isEmpty()) {
            return new self('', false, false, 0.0);
        }

        $rows = $herbs->map(fn ($h) => [
            'name' => $h->name,
            'percentage' => (float) ($h->pivot->percentage ?? 0),
            'is_bio' => $mode->herbIsBio($h),
        ])->all();

        $anyBio = false;
        $anyNonBio = false;
        $nonBioPercent = 0.0;
        foreach ($rows as $r) {
            if ($r['is_bio']) {
                $anyBio = true;
            } else {
                $anyNonBio = true;
                $nonBioPercent += $r['percentage'];
            }
        }
        $allBio = $anyBio && ! $anyNonBio;
        $isMixed = $anyBio && $anyNonBio;

        $parts = array_map(function (array $r) use ($mode, $isMixed) {
            if ($r['is_bio']) {
                // Asterisk only when the list is mixed — on a fully-bio list the
                // closing sentence applies to all entries, so the marker would
                // just be visual noise.
                return $isMixed ? $r['name'].'*' : $r['name'];
            }
            // Non-bio entry. Drop the percentage in `none` mode (no bio claim
            // anywhere → nothing to disclaim against).
            if ($mode === BioMode::None) {
                return $r['name'];
            }
            $pctStr = rtrim(rtrim(number_format($r['percentage'], 1, ',', ''), '0'), ',');

            return ($pctStr !== '' ? $pctStr.'% ' : '').$r['name'];
        }, $rows);

        return new self(
            text: implode(', ', $parts),
            anyBio: $anyBio,
            allBio: $allBio,
            nonBioPercent: $nonBioPercent,
        );
    }
}
