<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Product\Enums\ProductCategory;
use App\Modules\Product\Models\Product;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'price' => Money::fromRubles(fake()->numberBetween(100, 10000)),
            'weight' => fake()->numberBetween(100, 5000),
            'category' => fake()->randomElement(ProductCategory::cases()),
        ];
    }

    public function pizza(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ProductCategory::Pizza,
        ]);
    }

    public function drink(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ProductCategory::Drink,
        ]);
    }
}
