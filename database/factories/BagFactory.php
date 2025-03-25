<?php

namespace Database\Factories;

use App\Models\Bag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bag>
 */
class BagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bio = $this->faker->boolean(90);
        $size = $this->faker->randomElement([2000, 5000, 5000, 5000, 10000]);
        $trashed = $this->faker->boolean() ? $this->faker->numberBetween(0, $size / 10) : 0;

        return [
            'charge' => $this->faker->dateTimeBetween('-5 years')->format('ynj'),
            'bio' => $bio,
            'size' => $size,
            'specification' => ($bio ? 'Bio ' : '') . $this->faker->randomElement(['geschnitten', 'ganz', 'gerebelt']),
            'trashed' => $trashed,
            'bestbefore' => $this->faker->dateTimeBetween('-6 months', '+2 years')->format('Y-m-d'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Bag $bag) {
            if ($this->faker->boolean(20)) $bag->discard();
        });
    }
}
