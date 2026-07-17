<?php

namespace Database\Factories;

use App\Models\BioInspector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BioInspector>
 */
class BioInspectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company' => $this->faker->company().' AG',
            'label' => 'DE-ÖKO-'.$this->faker->numberBetween(1, 70),
        ];
    }
}
