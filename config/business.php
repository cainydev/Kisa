<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business identity
    |--------------------------------------------------------------------------
    |
    | The legal operator details as they appear in the Impressum
    | (kraeuter-wege.de/impressum). Used on audit / traceability documents
    | (Warenweg, Mengenfluss) so they carry the correct legal entity and
    | Öko-Kontrollstelle. Kept separate from config/labels.php, whose "brand"
    | block drives the retail product-label design.
    */

    'name' => 'Kräuter & Wege',
    'owner' => 'Inhaber Marcus Wagner',

    'address' => [
        'street' => 'Wickersdorfer Str. 1',
        'postal_code' => '35274',
        'city' => 'Kirchhain-Emsdorf',
    ],

    'contact' => [
        'phone' => '+49 (0) 6425 – 702 99 32',
        'email' => 'info@kraeuter-wege.de',
        'website' => 'kraeuter-wege.de',
    ],

    /*
    | Öko-Kontrollstelle (organic control body) and this operation's control
    | number, per the Impressum.
    */
    'organic' => [
        'control_body' => 'Gesellschaft für Ressourcenschutz mbH (GfRS)',
        'control_body_code' => 'DE-ÖKO-039',
        'control_number' => 'DE-HE-039-09312-B',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document extraction (OCR / AI)
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI-assisted extraction of uploaded documents
    | (organic certificates, delivery notes). The provider/model are resolved
    | by laravel/ai; swap them here to change the extraction engine in one
    | place. The provider's API key lives in the matching config/ai.php entry
    | (e.g. GEMINI_API_KEY for the "gemini" provider).
    */
    'document_extraction' => [
        'provider' => env('DOCUMENT_EXTRACTION_PROVIDER', 'gemini'),
        'model' => env('DOCUMENT_EXTRACTION_MODEL', 'gemini-3.1-flash-lite'),
        'timeout' => env('DOCUMENT_EXTRACTION_TIMEOUT', 120),
    ],

];
