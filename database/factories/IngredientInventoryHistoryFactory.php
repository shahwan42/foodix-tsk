<?php

namespace Database\Factories;

use App\Models\IngredientInventoryHistory;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IngredientInventoryHistory>
 */
class IngredientInventoryHistoryFactory extends Factory
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

    public function add(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'event' => IngredientInventoryHistory::EVENT_ADD,
        ]);
    }

    public function burgerBeefWeight(int $quantity = 1): Factory
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $quantity * Product::BURGER_INGREDIENTS[Ingredient::BEEF],
        ]);
    }

    public function bigBurgerBeefWeight(int $quantity = 1): Factory
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $quantity * Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF],
        ]);
    }

    public function burgerCheeseWeight(int $quantity = 1): Factory
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $quantity * Product::BURGER_INGREDIENTS[Ingredient::CHEESE],
        ]);
    }

    public function bigBurgerCheeseWeight(int $quantity = 1): Factory
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $quantity * Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE],
        ]);
    }

    public function burgerOnionWeight(int $quantity = 1): Factory
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $quantity * Product::BURGER_INGREDIENTS[Ingredient::ONION],
        ]);
    }

    public function bigBurgerOnionWeight(int $quantity = 1): Factory
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $quantity * Product::BIG_BURGER_INGREDIENTS[Ingredient::ONION],
        ]);
    }

    public function subtract(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
    }
}
