<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // General
        $this->call(TableSettingSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ProductTypeSeeder::class);

        // Supplier & Bio
        $this->call(BioInspectorSeeder::class);
        $this->call(SupplierSeeder::class);

        // Data
        $this->call(RoseanumSeeder::class);
        $this->call(RuthSeeder::class);
        $this->call(DrahtSeeder::class);
        $this->call(HerbPercentageSeeder::class);
        $this->call(EinzelSeeder::class);
    }
}
