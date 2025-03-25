<?php

namespace Database\Factories;

use App\Models\Herb;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class HerbFactory extends Factory
{
    protected $model = Herb::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'fullname' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'supplier_id' => Supplier::inRandomOrder()->first(),
        ];
    }
}
