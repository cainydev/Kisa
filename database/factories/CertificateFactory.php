<?php

namespace Database\Factories;

use App\Enums\CertificateActivity;
use App\Enums\ProductCategory;
use App\Models\BioInspector;
use App\Models\Certificate;
use App\Models\Supplier;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $validFrom = $this->faker->dateTimeBetween('-3 years', '-1 year');
        $validUntil = (clone $validFrom)->modify('+2 years');

        return [
            'supplier_id' => Supplier::inRandomOrder()->first() ?? Supplier::factory(),
            'bio_inspector_id' => BioInspector::inRandomOrder()->first() ?? BioInspector::factory(),
            'certificate_number' => $this->faker->bothify('??##??###??##'),
            'activities' => [CertificateActivity::Preparation, CertificateActivity::Import],
            'product_categories' => [ProductCategory::UnprocessedPlants, ProductCategory::ProcessedFood],
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'issued_at' => $validFrom,
            'issued_place' => $this->faker->city(),
        ];
    }

    /**
     * State: the certificate is valid on the given date.
     */
    public function validOn(DateTimeInterface $date): static
    {
        return $this->state(fn (): array => [
            'valid_from' => (clone $date)->modify('-1 month'),
            'valid_until' => (clone $date)->modify('+1 year'),
        ]);
    }
}
