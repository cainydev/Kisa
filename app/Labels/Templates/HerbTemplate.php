<?php

namespace App\Labels\Templates;

use App\Labels\AbstractLabelTemplate;
use App\Labels\BioMode;
use App\Labels\Param;
use App\Models\Product;

class HerbTemplate extends AbstractLabelTemplate
{
    public function key(): string
    {
        return 'herb';
    }

    public function name(): string
    {
        return 'Einzelkraut (105×170 mm)';
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
            'front' => 'labels.templates.herb-front',
            'back' => 'labels.templates.herb-back',
        ];
    }

    public function parameters(): array
    {
        $brandColors = config('labels.brand.colors');

        return [
            // Title for the front. Big uppercase rendering of the product
            // name. Splits from backTitle so they can diverge if needed
            // (e.g. an abbreviation on the front, full name on the back).
            Param::make('frontTitle')->string()->hyphenate()->label('Titel (Vorderseite)')
                ->auto(fn (?Product $p) => $p ? mb_strtoupper($p->name) : 'KRÄUTER'),

            // Title for the back. Smaller, mixed-case rendering above the
            // ingredients block. Same auto default as frontTitle but
            // independently overridable.
            Param::make('backTitle')->string()->hyphenate()->label('Titel (Rückseite)')
                ->auto(fn (?Product $p) => $p ? mb_strtoupper($p->name) : 'KRÄUTER'),

            // Per-label override for the front title's font size. Leave blank
            // to use the template default (9.9 mm). Bump down when long
            // herb names won't fit on a single line at the default size.
            Param::make('titleFontSize')->number()->label('Titel-Schriftgröße')
                ->range(4, 14, 0.1, 'mm'),

            // Per-label override for the back body text size (Inhaltsstoffe,
            // Zubereitungstext, Sicherheitshinweis, Anwendung). Leave blank
            // to use the template default. Bump down when long bodies don't
            // fit; bump up if there's room to spare.
            Param::make('bodyFontSize')->number()->label('Text-Schriftgröße')
                ->range(2, 5, 0.05, 'mm'),

            // Subtitle under the title on the front.
            Param::make('subtitle')->string()->hyphenate()->label('Untertitel')
                ->default('aus kontrolliert biologischem Anbau'),

            // Bio mode. Drives ingredient prefix ("Bio " when bio), the
            // closing "aus kontrolliert biologischem Anbau …" sentence,
            // and the BIO/EU-leaf seal eligibility on the back. Default
            // bio for Einzelkraut — virtually all single herbs we sell
            // are certified organic.
            Param::make('bioMode')->select(BioMode::options())->label('Bio-Modus')
                ->default(BioMode::Bio->value),

            // The main visual. Required — per-herb illustration.
            Param::make('artwork')->image()->label('Kräuterzeichnung')->required(),

            // Fine-tuning for the artwork placement on the front.
            Param::make('artworkRotate')->number()->label('Drehung')
                ->range(-180, 180, 1, '°')->default(0),
            Param::make('artworkScale')->number()->label('Skalierung')
                ->range(0.5, 2, 0.05, '×')->default(1),
            Param::make('artworkOffsetX')->number()->label('Verschiebung horizontal')
                ->range(-30, 30, 0.5, 'mm')->default(0),
            Param::make('artworkOffsetY')->number()->label('Verschiebung vertikal')
                ->range(-30, 30, 0.5, 'mm')->default(0),

            // Brand assets — attached once on the parent base label,
            // inherited by every concrete herb label.
            Param::make('brandLogo')->image()->shared()->label('Marken-Logo'),
            Param::make('bioSeal')->image()->shared()->label('BIO-Siegel'),
            Param::make('gruenPunkt')->image()->shared()->label('Grüner Punkt'),
            Param::make('euBioLeaf')->image()->shared()->label('EU-Bio-Blatt'),
            Param::make('prepAmountIcon')->image()->shared()->label('Icon Menge'),
            Param::make('prepTemperatureIcon')->image()->shared()->label('Icon Temperatur'),
            Param::make('prepTimeIcon')->image()->shared()->label('Icon Ziehzeit'),

            // Fonts — one file per slot. Upload once on the parent base label,
            // inherited by every concrete herb label.
            // The slots map 1:1 to the original InDesign weights of Avenir LT Std.
            Param::make('titleFont')->font()->shared()->label('Schrift Überschrift (Heavy)'),
            Param::make('bodyFont')->font()->shared()->label('Schrift Fließtext (Book)'),
            Param::make('italicFont')->font()->shared()->label('Schrift Kursiv (Roman Oblique)'),
            Param::make('subtitleFont')->font()->shared()->label('Schrift Untertitel (Light)'),
            Param::make('accentFont')->font()->shared()->label('Schrift Akzent (Medium)'),

            // Latin / scientific name shown in italics on the back ("Folia Urticae").
            Param::make('latinName')->string()->label('Lateinischer Name')
                ->default(''),

            // Display name used on the back ingredients line, e.g.
            // "Brennnesselblätter". Defaults to the product name; override
            // here if the product name carries extra qualifiers you don't
            // want on the label (rare — mostly the data should be clean).
            Param::make('displayName')->string()->hyphenate()->label('Anzeigename')
                ->auto(fn (?Product $p) => $p?->name ?? ''),

            // Cut form suffix on the ingredients line, e.g. "geschnitten",
            // "gerebelt", "ganz", "gemahlen". Per-label since it depends on
            // what's currently bagged. Empty string = no suffix.
            Param::make('cutForm')->string()->label('Schnitt')
                ->default('geschnitten'),

            // Optional usage / mode-of-application line shown above the
            // Inhaltsstoffe block, e.g. "Zur inneren und äußeren Anwendung"
            // or "Nur zur äußeren Anwendung". Empty = section is hidden.
            Param::make('usageNote')->string()->hyphenate()->label('Anwendung')
                ->default(''),

            // Heading for the primary preparation section. Editable per
            // label so a herb that's only used externally can show
            // "Zubereitung als Badezusatz" or similar.
            Param::make('preparationTitle')->string()->label('Titel Zubereitung')
                ->default('Zubereitungshinweise:'),

            // Brewing parameters shown as labels under the three icons.
            Param::make('prepAmount')->string()->label('Menge')
                ->default('1-2 TL'),
            Param::make('prepTemperature')->string()->label('Temperatur')
                ->default('100°C'),
            Param::make('prepTime')->string()->label('Ziehzeit')
                ->default('5-8 Min.'),

            // Body paragraph under the prep icons. The blade view auto-builds
            // it from the displayName + brewing params when this is left
            // empty, so an override of `displayName` flows through naturally.
            Param::make('preparationBody')->string()->hyphenate()->label('Zubereitungstext'),

            // Optional second preparation section. Renders below the first
            // one when either field is set:
            //   - title only:  not rendered (no body to show)
            //   - body only:   plain paragraph with no heading
            //   - both:        heading + paragraph (e.g. "Zubereitung als
            //                  Badezusatz" + the bath instructions for
            //                  Dostkraut)
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

            // Brand colors. Defaults from config; override per label for special variants.
            Param::make('headingColor')->color()->label('Farbe Überschrift')
                ->default($brandColors['heading'] ?? '#d8dc8e'),
            Param::make('subtitleColor')->color()->label('Farbe Untertitel')
                ->default($brandColors['subtitle'] ?? '#6f7070'),
            Param::make('textColor')->color()->label('Farbe Text')
                ->default($brandColors['text'] ?? '#1c1d1c'),

            // Vorschau-Hilfen (nur sichtbar in der Vorschau, nicht im Druck — manuell ausblenden vor PDF-Export).
            Param::make('showStickerOutline')->boolean()->label('Aufkleber-Umriss zeigen (95×48 mm)')
                ->default(false),
        ];
    }
}
