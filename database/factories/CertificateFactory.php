<?php

namespace Database\Factories;

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
            'certificate_number' => $this->faker->bothify('??##??###??##'),
            'operator_name' => $this->faker->company(),
            'control_body' => $this->faker->randomElement(['ABCERT AG', 'KIWA', 'Grünstempel']),
            'control_body_code' => 'DE-ÖKO-'.$this->faker->numberBetween(1, 70),
            'activities' => $this->faker->randomElement(['Aufbereitung', 'Einfuhr', 'Aufbereitung, Einfuhr']),
            'product_categories' => 'd) verarbeitete landwirtschaftliche Erzeugnisse',
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
