<?php

namespace Database\Factories;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(100, 10000),
            'weight' => $this->faker->numberBetween(100, 5000),
            'category' => $this->faker->randomElement(['пицца', 'напиток']),
        ];
    }
}
