<?php

namespace Database\Factories;

use App\Models\Bottle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bottle>
 */
class BottleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'note' => $this->faker->text(),
            'date' => $this->faker->dateTimeBetween('-6 months'),
        ];
    }
}
