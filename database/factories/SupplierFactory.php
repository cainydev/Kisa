<?php

namespace Database\Factories;

use App\Models\BioInspector;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = $this->faker->company();

        return [
            'company' => $company,
            'shortname' => $this->faker->lastName(),
            'contact' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'bio_inspector_id' => BioInspector::inRandomOrder()->first() ?? BioInspector::factory(),
        ];
    }
}
