<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ingredient;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // create ingredients
        $beef = Ingredient::factory()->beef()->create();
        $cheese = Ingredient::factory()->cheese()->create();
        $onion = Ingredient::factory()->onion()->create();

        // restock ingredients
        $beef->restock();
        $cheese->restock();
        $onion->restock();

        // create products
        // create burger
        $burger = Product::factory()->burger()->create();
        $burger->ingredients()->attach([
            $beef->id => ['ingredient_weight' => Product::BURGER_INGREDIENTS[Ingredient::BEEF]],
            $cheese->id => ['ingredient_weight' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE]],
            $onion->id => ['ingredient_weight' => Product::BURGER_INGREDIENTS[Ingredient::ONION]]
        ]);

        // create big burger
        $bigBurger = Product::factory()->bigBurger()->create();
        $bigBurger->ingredients()->attach([
            $beef->id => ['ingredient_weight' => Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF]],
            $cheese->id => ['ingredient_weight' => Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE]],
            $onion->id => ['ingredient_weight' => Product::BIG_BURGER_INGREDIENTS[Ingredient::ONION]]
        ]);
    }
}
