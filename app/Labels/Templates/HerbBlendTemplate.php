<?php

namespace App\Labels\Templates;

use App\Labels\AbstractLabelTemplate;
use App\Labels\BioMode;
use App\Labels\Param;
use App\Models\Product;

class HerbBlendTemplate extends AbstractLabelTemplate
{
    public function key(): string
    {
        return 'herb-blend';
    }

    public function name(): string
    {
        return 'Mischung nach Draht (105×170 mm)';
    }

    public function subjects(): array
    {
        return [Product::class];
    }

    public function dimensions(): array
    {
        return ['width_mm' => 105, 'height_mm' => 170];
    }

    public function pages(): array
    {
        return [
            'front' => 'labels.templates.herb-blend-front',
            'back' => 'labels.templates.herb-blend-back',
        ];
    }

    /**
     * Pull the tea number from a product name like "Nr. 26 Bio-Kräuterteemischung".
     */
    protected function teaNumber(?Product $p): ?int
    {
        if (! $p) {
            return null;
        }
        if (preg_match('/Nr\.\s*(\d+)/u', $p->name, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    public function parameters(): array
    {
        $brandColors = config('labels.brand.colors');

        return [
            // Title — "BIO-KRÄUTERTEE NR. 26".
            Param::make('title')->string()->hyphenate()->label('Titel')
                ->auto(function (?Product $p) {
                    $no = $this->teaNumber($p);
                    if ($no === null) {
                        return 'BIO-KRÄUTERTEE';
                    }

                    return "BIO-KRÄUTERTEE NR. {$no}";
                }),

            // Per-label override for the front title's font size. Leave blank
            // to use the template default (9.10 mm).
            Param::make('titleFontSize')->number()->label('Titel-Schriftgröße')
                ->range(4, 14, 0.1, 'mm'),

            // Per-label override for the back body text size (Inhaltsstoffe,
            // Zubereitungstext, Sicherheitshinweis, Anwendung). Leave blank
            // to use the template default. Bump down when long bodies don't
            // fit (Hautwaschung, Tinktur, …); bump up if room to spare.
            Param::make('bodyFontSize')->number()->label('Text-Schriftgröße')
                ->range(2, 5, 0.05, 'mm'),

            // Subtitle under the title on the front.
            Param::make('subtitle')->string()->hyphenate()->label('Untertitel')
                ->default('Heimische Kräuter aus kontrolliert biologischem Anbau'),

            // Bio mode. Controls how each ingredient is rendered and whether
            // the bio seals / EU leaf / footnote appear:
            //   - none       : every ingredient is non-bio (`{percent}% name`)
            //   - bio        : every ingredient is certified bio (`name*`)
            //   - from_stock : per-herb, computed at render time from current
            //                  bag stock (≥ 100 g remaining; all bio ⇒ bio).
            Param::make('bioMode')->select(BioMode::options())->label('Bio-Modus')
                ->default(BioMode::Bio->value),

            // Drives the back-side title and the dominant green of the prep
            // icons (#C5C95C in the source SVGs is recolored to this value).
            Param::make('accentColor')->color()->shared()->label('Akzentfarbe')
                ->default('#C5C95C'),

            // The main visual. Required — full-bleed background for the blend variant.
            Param::make('background')->image()->shared()->label('Hintergrundbild')->required(),

            // Brand assets — attached once on the parent base label,
            // inherited by every concrete child label.
            Param::make('brandLogo')->image()->shared()->label('Marken-Logo'),
            Param::make('bioSeal')->image()->shared()->label('BIO-Siegel'),
            Param::make('gruenPunkt')->image()->shared()->label('Grüner Punkt'),
            Param::make('euBioLeaf')->image()->shared()->label('EU-Bio-Blatt'),
            Param::make('prepAmountIcon')->image()->shared()->label('Icon Menge'),
            Param::make('prepTemperatureIcon')->image()->shared()->label('Icon Temperatur'),
            Param::make('prepTimeIcon')->image()->shared()->label('Icon Ziehzeit'),

            // Fonts — one file per slot.
            Param::make('titleFont')->font()->shared()->label('Schrift Überschrift (Heavy)'),
            Param::make('bodyFont')->font()->shared()->label('Schrift Fließtext (Book)'),
            Param::make('italicFont')->font()->shared()->label('Schrift Kursiv (Roman Oblique)'),
            Param::make('subtitleFont')->font()->shared()->label('Schrift Untertitel (Roman)'),
            Param::make('accentFont')->font()->shared()->label('Schrift Akzent (Medium)'),

            // Ingredient list on the back. When empty the blade view builds
            // it from the product's recipe (descending percentage, LMIV) and
            // applies the bio mode. Override here to hand-write the list.
            Param::make('inhaltsstoffe')->string()->hyphenate()->label('Inhaltsstoffe'),

            // Optional usage / mode-of-application line shown above the
            // Inhaltsstoffe block, e.g. "Nur zur äußeren Anwendung".
            // Empty = section is hidden.
            Param::make('usageNote')->string()->hyphenate()->label('Anwendung')
                ->default(''),

            // Heading for the primary preparation section. Editable per
            // label so a blend that's only used externally (Sitzbad,
            // Tinktur, Leibumschlag, …) can show "Zubereitung als
            // Sitzbad" or similar.
            Param::make('preparationTitle')->string()->label('Titel Zubereitung')
                ->default('Zubereitungshinweise:'),

            // Brewing parameters shown as labels under the three icons.
            // Defaults match the dominant pattern across the Mischtee range
            // (49 of 58 variants use "2 TL · 90-100°C · 5-8 Min."). The few
            // outliers (Gurgeltee, Augenbad, etc.) override per-label.
            Param::make('prepAmount')->string()->label('Menge')
                ->default('2 TL'),
            Param::make('prepTemperature')->string()->label('Temperatur')
                ->default('90-100°C'),
            Param::make('prepTime')->string()->label('Ziehzeit')
                ->default('5-8 Min.'),

            // Body paragraph under the prep icons.
            // Tokens {prepAmount} and {prepTimeLong} are replaced at render time so
            // the body stays in sync with the caption values. {prepTimeLong} also
            // expands abbreviations like "5 Min." → "5 Minuten".
            Param::make('preparationBody')->string()->hyphenate()->label('Zubereitungstext')
                ->default('{prepAmount} Teelöffel mit ca. 250 ml siedendem Wasser übergießen und nach {prepTimeLong} abseihen.'),

            // Optional second preparation section. Renders below the first
            // one when either field is set:
            //   - title only:  not rendered (no body to show)
            //   - body only:   plain paragraph with no heading
            //   - both:        heading + paragraph (e.g. "Anwendung der
            //                  Tinktur" + the application instructions for
            //                  Nr. 76, or "Anwendungshinweise" for Nr. 613).
            // No icon row — that belongs to the primary preparation.
            Param::make('preparation2Title')->string()->label('Titel zweite Zubereitung')
                ->default(''),
            Param::make('preparation2Body')->string()->hyphenate()->label('Zweite Zubereitung')
                ->default(''),

            // Safety hint paragraph.
            Param::make('safetyHint')->string()->hyphenate()->label('Sicherheitshinweis')
                ->default('Immer mit sprudelnd kochendem Wasser aufgießen und mindestens 5 Minuten ziehen lassen! Nur so erhalten Sie ein sicheres Lebensmittel. Vor Licht und Feuchtigkeit geschützt aufbewahren.'),

            // Small left-aligned hint near the seals row.
            Param::make('fillVolumeHint')->string()->shared()->label('Füllhöhen-Hinweis')
                ->default('Füllhöhe technisch bedingt!'),

            // Brand colors.
            Param::make('subtitleColor')->color()->shared()->label('Farbe Untertitel')
                ->default($brandColors['subtitle'] ?? '#6f7070'),
            Param::make('textColor')->color()->shared()->label('Farbe Text')
                ->default($brandColors['text'] ?? '#1c1d1c'),

            // Vorschau-Hilfen.
            Param::make('showStickerOutline')->boolean()->label('Aufkleber-Umriss zeigen (95×48 mm)')
                ->default(false),
        ];
    }
}
