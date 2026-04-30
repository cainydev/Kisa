<?php

use App\Labels\Templates\HerbBlendTemplate;
use App\Labels\Templates\HerbTemplate;
use App\Labels\Templates\RuthsBlendTemplate;

return [
    /*
    |--------------------------------------------------------------------------
    | Label Templates
    |--------------------------------------------------------------------------
    |
    | List of LabelTemplate implementations available to the system. Each
    | template declares a Blade view, a human-readable name, and a parameter
    | schema (auto/user, image/string/number/color). The TemplateRegistry
    | indexes them by their key() — that key is what gets stored on
    | label_pages rows.
    */

    'templates' => [
        HerbTemplate::class,
        HerbBlendTemplate::class,
        RuthsBlendTemplate::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | Constants used by every label so they can be edited in one place.
    */

    'brand' => [
        'name' => 'kräuter & wege GbR',
        'address_lines' => [
            'Ellerweg 4, D-35282 Rauschenberg',
            'info@kraeuter-wege.de - www.kraeuter-wege.de',
        ],
        'oeko_code' => 'DE-ÖKO-039',
        'oeko_origin' => 'EU-/Nicht-EU-Landwirtschaft',

        'colors' => [
            'heading' => '#d8dc8e',
            'subtitle' => '#6f7070',
            'text' => '#1c1d1c',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Browsershot binaries
    |--------------------------------------------------------------------------
    |
    | Routed through config/ so they survive `php artisan config:cache`.
    | Calling env() directly inside the renderer would return null after the
    | config cache is built (Laravel only loads .env when the cache is absent).
    */

    'browsershot' => [
        'chromium_path' => env('BROWSERSHOT_CHROMIUM_PATH'),
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
    ],
];
