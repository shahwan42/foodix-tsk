<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
class IngredientFactory extends Factory
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

    public function beef(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'name' => Ingredient::BEEF,
            'inventory_level' => Ingredient::INVENTORY_LEVEL[Ingredient::BEEF],
        ]);
    }

    public function cheese(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'name' => Ingredient::CHEESE,
            'inventory_level' => Ingredient::INVENTORY_LEVEL[Ingredient::CHEESE],
        ]);
    }

    public function onion(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'name' => Ingredient::ONION,
            'inventory_level' => Ingredient::INVENTORY_LEVEL[Ingredient::ONION],
        ]);
    }
}
