<?php

namespace Database\Seeders;

use App\Models\Bottle;
use App\Models\BottlePosition;
use Illuminate\Database\Seeder;
use function now;

class BottleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bottle::factory(3, ['date' => now()])
            ->has(BottlePosition::factory()->count(5), 'positions')
            ->create();

        Bottle::factory(2, ['date' => now()->subDay()])
            ->has(BottlePosition::factory()->count(5), 'positions')
            ->create();

        $this->command->withProgressBar(range(1, 1000), function () {
            $bottle = Bottle::factory()
                ->has(BottlePosition::factory()->count(5), 'positions')
                ->createOne();

            $bottle->update([
                'description' => $bottle->generateDescription()
            ]);
        });


    }
}
