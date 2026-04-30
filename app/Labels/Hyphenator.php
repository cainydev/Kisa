<?php

namespace App\Labels;

use Vanderlee\Syllable\Syllable;

/**
 * Knuth–Liang German hyphenator wrapping vanderlee/syllable. Inserts U+00AD
 * soft hyphens at legal break points so a Chromium build without an active
 * Hyphenation component still produces correct line breaks for long German
 * compounds (e.g. Brennnessel\u{00AD}blätter).
 *
 * Bound as a `scoped` singleton in AppServiceProvider so the underlying
 * Syllable instance is reused for the request lifetime (Octane-safe — the
 * scoped binding is reset between requests).
 */
class Hyphenator
{
    private ?Syllable $syllable = null;

    public function hyphenate(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        return $this->syllable()->hyphenateText($text);
    }

    private function syllable(): Syllable
    {
        if ($this->syllable) {
            return $this->syllable;
        }
        $cacheDir = storage_path('framework/cache/syllable');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        Syllable::setCacheDir($cacheDir);
        $s = new Syllable('de');
        $s->setHyphen("\u{00AD}");
        // Skip very short words entirely — adds noise without saving space.
        $s->setMinWordLength(6);

        return $this->syllable = $s;
    }
}
