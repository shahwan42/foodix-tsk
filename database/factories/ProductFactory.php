<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }

    public function burger(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'name' => Product::BURGER,
        ]);
    }

    public function bigBurger(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'name' => Product::BIG_BURGER,
        ]);
    }
}
