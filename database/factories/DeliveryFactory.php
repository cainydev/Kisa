<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-2 years');

        return [
            'delivered_date' => $date,
            'bio_inspection' => [],
            'created_at' => $date,
            'updated_at' => $date,
            'supplier_id' => Supplier::inRandomOrder()->first(),
            'user_id' => User::inRandomOrder()->first(),
        ];
    }
}
