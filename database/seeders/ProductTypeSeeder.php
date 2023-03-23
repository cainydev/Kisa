<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductType::create([
            'name' => 'Mischung nach Draht',
        ]);

        ProductType::create([
            'name' => 'Einzelkraut',
        ]);

        ProductType::create([
            'name' => 'Pfennighaus',
        ]);

        ProductType::create([
            'name' => 'Roseanum Schönbrunn',
        ]);
    }
}
