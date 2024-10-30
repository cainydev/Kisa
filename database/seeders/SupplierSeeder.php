<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        Supplier::create([
            'company' => 'Alfred Galke GmbH',
            'shortname' => 'Galke',
            'contact' => 'Alfred Galke',
            'email' => 'info@galke.com',
            'phone' => '0532786810',
            'website' => 'www.galke.com',
            'bio_inspector_id' => 1,
        ]);
        Supplier::create([
            'company' => 'Dragonspice Naturwaren',
            'shortname' => 'Dragonspice',
            'contact' => 'Christian Recke',
            'email' => 'info@dragonspice.de',
            'phone' => '071215939980',
            'website' => 'www.dragonspice.de',
            'bio_inspector_id' => 2,
        ]);
        Supplier::create([
            'company' => 'EDEL KRAUT GmbH',
            'shortname' => 'Edelkraut',
            'contact' => 'Amin Golbolakh',
            'email' => 'info@edel-kraut.de',
            'phone' => '08992777567',
            'website' => 'www.edel-kraut.de',
            'bio_inspector_id' => 3,
        ]);
        Supplier::create([
            'company' => 'Mediterranean Soil',
            'shortname' => 'Medi-Soil',
            'contact' => 'Maria Nteoudi',
            'email' => 'mediterraneansoil@gmail.com',
            'phone' => '+30-2541025108',
            'website' => 'www.mediterraneansoil.com',
            'bio_inspector_id' => 4,
        ]);
        Supplier::create([
            'company' => 'Miraherba GmbH',
            'shortname' => 'Miraherba',
            'contact' => 'Sabine Deutscher',
            'email' => 'info@miraherba.com',
            'phone' => '07141 1423570',
            'website' => 'www.miraherba.de',
            'bio_inspector_id' => 3,
        ]);
        Supplier::create([
            'company' => 'Wollenhaupt Tee GmbH',
            'shortname' => 'Wollenhaupt',
            'contact' => 'Dirk Wollenhaupt',
            'email' => 'info@wollenhaupt.com',
            'phone' => '040 728 30 300',
            'website' => 'www.wollenhaupt.com',
            'bio_inspector_id' => 2,
        ]);
    }
}
