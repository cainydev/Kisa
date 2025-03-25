<?php

namespace Database\Factories;

use App\Models\BottlePosition;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BottlePosition>
 */
class BottlePositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variant_id' => Variant::inRandomOrder()->first()->id,
            'count' => $this->faker->randomElement([1, 2, 5, 10, 10, 10, 20]),
            'uploaded' => true,
        ];
    }
}
