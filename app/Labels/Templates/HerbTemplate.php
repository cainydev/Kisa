<?php

namespace App\Labels\Templates;

use App\Labels\AbstractLabelTemplate;
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

        $cleanName = function (?Product $p): string {
            if (! $p) {
                return 'Kräuter';
            }
            // Drop a leading "Bio " prefix.
            $name = preg_replace('/^Bio\s+/iu', '', $p->name);
            // Drop any word that's a (case-insensitive) substring of another word.
            $words = array_values(array_filter(preg_split('/\s+/u', $name), 'strlen'));
            $kept = [];
            foreach ($words as $i => $w) {
                $lw = mb_strtolower($w);
                $isRedundant = false;
                foreach ($words as $j => $other) {
                    if ($i === $j) {
                        continue;
                    }
                    if ($lw !== mb_strtolower($other) && mb_stripos($other, $lw) !== false) {
                        $isRedundant = true;
                        break;
                    }
                }
                if (! $isRedundant) {
                    $kept[] = $w;
                }
            }
            // Collapse exact-duplicates.
            $seen = [];
            $kept = array_values(array_filter($kept, function ($w) use (&$seen) {
                $k = mb_strtolower($w);
                if (isset($seen[$k])) {
                    return false;
                }
                $seen[$k] = true;

                return true;
            }));

            return implode(' ', $kept) ?: $name;
        };

        return [
            // Title shown big on front, smaller on back. Defaults to a cleaned product name uppercased.
            Param::make('title')->string()->label('Titel')
                ->auto(fn (?Product $p) => mb_strtoupper($cleanName($p))),

            // Subtitle under the title on the front.
            Param::make('subtitle')->string()->label('Untertitel')
                ->default('aus kontrolliert biologischem Anbau'),

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
            Param::make('brandFont')->font()->shared()->label('Schrift Markenname (Black)'),

            // Latin / scientific name shown in italics on the back ("Folia Urticae").
            Param::make('latinName')->string()->label('Lateinischer Name')
                ->default(''),

            // Display name used on the back — sentence-case clean name, e.g. "Brennnesselblätter".
            Param::make('displayName')->string()->label('Anzeigename')
                ->auto(fn (?Product $p) => $cleanName($p)),

            // Brewing parameters shown as labels under the three icons.
            Param::make('prepAmount')->string()->label('Menge')
                ->default('1-2 TL'),
            Param::make('prepTemperature')->string()->label('Temperatur')
                ->default('100°C'),
            Param::make('prepTime')->string()->label('Ziehzeit')
                ->default('5 Min.'),

            // Body paragraph under the prep icons.
            Param::make('preparationBody')->string()->label('Zubereitungstext')
                ->auto(fn (?Product $p) => '1-2 Teelöffel '.$cleanName($p).' mit ca. 250 ml siedendem Wasser übergießen und nach 5-8 Minuten abseihen.'),

            // Safety hint paragraph.
            Param::make('safetyHint')->string()->label('Sicherheitshinweis')
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
        ];
    }
}
