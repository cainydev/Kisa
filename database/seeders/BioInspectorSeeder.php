<?php

namespace Database\Seeders;

use App\Models\BioInspector;
use Illuminate\Database\Seeder;

class BioInspectorSeeder extends Seeder
{
    public function run()
    {
        BioInspector::create([
            'company' => 'Kiwa BCS Öko-Garantie GmbH',
            'label' => 'DE-ÖKO-001',
            'country' => 'DE',
        ]);
        BioInspector::create([
            'company' => 'ABCERT AG',
            'label' => 'DE-ÖKO-006',
            'country' => 'DE',
        ]);
        BioInspector::create([
            'company' => 'ÖKOP Zertifizierungs GmbH',
            'label' => 'DE-ÖKO-037',
            'country' => 'DE',
        ]);
        BioInspector::create([
            'company' => 'EUROCERT SA',
            'label' => 'GR-BIO-17',
            'country' => 'GR',
        ]);
    }
}
