<?php

namespace App\Labels\Templates;

use App\Labels\AbstractLabelTemplate;
use App\Labels\BioMode;
use App\Labels\Param;
use App\Models\Product;

class RuthsBlendTemplate extends AbstractLabelTemplate
{
    public function key(): string
    {
        return 'ruths-blend';
    }

    public function name(): string
    {
        return 'Mischung nach Pfennighaus (105×170 mm)';
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
            'front' => 'labels.templates.ruths-blend-front',
            'back' => 'labels.templates.ruths-blend-back',
        ];
    }

    public function parameters(): array
    {
        $brandColors = config('labels.brand.colors');

        return [
            // Title — e.g. "Ruths Frauen!Power".
            Param::make('title')->string()->hyphenate()->label('Titel')
                ->auto(fn (?Product $p) => $p?->name ?? 'Ruths Mischung'),

            // Per-label override for the front title's font size. Leave blank
            // to use the template default (9.10 mm).
            Param::make('titleFontSize')->number()->label('Titel-Schriftgröße')
                ->range(4, 14, 0.1, 'mm'),

            // Per-label override for the back body text size (Inhaltsstoffe,
            // Zubereitungstext, Sicherheitshinweis, Anwendung). Leave blank
            // to use the template default. Bump down when long bodies don't
            // fit; bump up if there's room to spare.
            Param::make('bodyFontSize')->number()->label('Text-Schriftgröße')
                ->range(2, 5, 0.05, 'mm'),

            // Subtitle under the title on the front. Pfennighaus copy:
            // "vorwiegend" (not "kontrolliert"), since the line isn't 100 % bio.
            Param::make('subtitle')->string()->hyphenate()->label('Untertitel')
                ->default('Heimische Kräuter aus vorwiegend biologischem Anbau'),

            // Bio mode. Controls how each ingredient is rendered and whether
            // the bio seals / EU leaf / footnote appear:
            //   - none       : every ingredient is non-bio (`{percent}% name`)
            //   - bio        : every ingredient is certified bio (`name*`)
            //   - from_stock : per-herb, computed at render time from current
            //                  bag stock (≥ 100 g remaining; all bio ⇒ bio).
            // Pfennighaus is partial-bio by default → from_stock.
            Param::make('bioMode')->select(BioMode::options())->label('Bio-Modus')
                ->default(BioMode::FromStock->value),

            // Drives the back-side title and the dominant green of the prep
            // icons (#C5C95C in the source SVGs is recolored to this value).
            Param::make('accentColor')->color()->shared()->label('Akzentfarbe')
                ->default('#C5C95C'),

            // The main visual — full-bleed background for the front.
            Param::make('background')->image()->shared()->label('Hintergrundbild')->required(),

            // Centered logo overlay on the front (e.g. "Ruth" portrait/logo).
            // Shared on the parent base label and reused by every product.
            Param::make('ruthLogo')->image()->shared()->label('Ruth Logo'),

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

            // Brewing parameters shown as labels under the three icons.
            Param::make('prepAmount')->string()->label('Menge')
                ->default('1 TL'),
            Param::make('prepTemperature')->string()->label('Temperatur')
                ->default('90-100°C'),
            Param::make('prepTime')->string()->label('Ziehzeit')
                ->default('5-8 Min.'),

            // Body paragraph under the prep icons. The blade view auto-builds
            // it from the product name, brewing temperature and steep time
            // when this is left empty (e.g. "1 Teelöffel Ruths Frauen!Power
            // mit ca. 250 ml siedendem Wasser übergießen und nach 5-8 Min.
            // abseihen."). Set a value here to override.
            Param::make('preparationBody')->string()->hyphenate()->label('Zubereitungstext'),

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
