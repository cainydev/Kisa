<?php

namespace App\Services\DocumentExtraction;

use App\Models\Herb;
use Illuminate\Support\Str;

/**
 * Matches a free-text herb name from an extracted delivery-note line to an
 * existing Herb record, so the review modal can pre-select the relation.
 * Returns the best candidate's id, or null when no confident match exists —
 * the user then picks manually.
 */
class HerbMatcher
{
    public function match(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $herbs = Herb::query()->get(['id', 'name', 'fullname']);

        // Exact (case-insensitive) match on name or fullname.
        $exact = $herbs->first(fn (Herb $herb): bool => in_array(
            Str::lower($name),
            [Str::lower((string) $herb->name), Str::lower((string) $herb->fullname)],
            true,
        ));

        if ($exact !== null) {
            return $exact->id;
        }

        // Containment either direction (handles "Anis" vs "Anis BIO geschnitten").
        $needle = Str::lower($name);
        $contains = $herbs->first(function (Herb $herb) use ($needle): bool {
            $candidates = [Str::lower((string) $herb->name), Str::lower((string) $herb->fullname)];

            foreach ($candidates as $candidate) {
                if ($candidate !== '' && (str_contains($needle, $candidate) || str_contains($candidate, $needle))) {
                    return true;
                }
            }

            return false;
        });

        return $contains?->id;
    }
}
