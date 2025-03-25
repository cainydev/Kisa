<?php

namespace Database\Seeders;

use App\Models\Bag;
use App\Models\Delivery;
use App\Models\Herb;
use Illuminate\Database\Seeder;
use Random\RandomException;

class BagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws RandomException
     */
    public function run(): void
    {
        $deliveries = Delivery::factory()->count(100)->create();

        foreach (Herb::all() as $herb) {
            $count = 4;//random_int(1, 8);
            Bag::factory()
                ->count($count)
                ->create([
                    'herb_id' => $herb->id,
                    'delivery_id' => $deliveries->random()->id,
                ]);
        }
    }
}
