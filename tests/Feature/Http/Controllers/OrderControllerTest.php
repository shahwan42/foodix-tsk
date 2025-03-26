<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientInventoryHistory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\IngredientInventoryAlert;
use App\Models\Order;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private Product $burger;
    private Ingredient $beef;
    private Ingredient $cheese;
    private Ingredient $onion;
    private Product $bigBurger;
    public function setUp(): void
    {
        parent::setUp();
        $this->ingredientsDB();
    }

    public function test_create_order_with_single_burger(): void
    {
        // == Arrange
        $this->singleBurgerIngredientsInventoryDB();
        $this->burgerDB();

        // == Act
        // create order
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 1
                ]
            ]
        ]);

        // == Assert
        // http response
        $response->assertStatus(201);
        $order = Order::first();
        $orderItem = $order->orderItems->first();
        $response->assertJson([
            'message' => 'Order created successfully',
            'result' => [
                'updated_at' => $order->updated_at->format('Y-m-d\TH:i:s.u\Z'),
                'created_at' => $order->created_at->format('Y-m-d\TH:i:s.u\Z'),
                'id' => $order->id,
                'order_items' => [
                    [
                        "id" => $orderItem->id,
                        "order_id" => $order->id,
                        "product_id" => $this->burger->id,
                        "quantity" => 1,
                        "created_at" => $orderItem->created_at->format('Y-m-d\TH:i:s.u\Z'),
                        "updated_at" => $orderItem->updated_at->format('Y-m-d\TH:i:s.u\Z')
                    ],
                ]
            ]
        ]);

        // db
        $this->assertDatabaseHas('orders', [
            'id' => 1,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => 1,
            'product_id' => $this->burger->id,
        ]);

        // ingredient inventory history
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->beef->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::BEEF],
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->cheese->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE],
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->onion->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::ONION],
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);

        // ingredient inventory zeros
        $this->beef->refresh();
        $this->assertEquals(0, $this->beef->inventory);
        $this->cheese->refresh();
        $this->assertEquals(0, $this->cheese->inventory);
        $this->onion->refresh();
        $this->assertEquals(0, $this->onion->inventory);
    }

    public function test_create_order_with_multiple_burgers(): void
    {
        // == Arrange
        $this->restockIngredients();
        $this->burgerDB();

        // == Act
        // create order
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 7
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Order created successfully',
        ]);

        // db
        $this->assertDatabaseHas('orders', [
            'id' => 2,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => 2,
            'product_id' => $this->burger->id,
            'quantity' => 7,
        ]);

        // ingredient inventory history
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->beef->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] * 7,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->cheese->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE] * 7,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->onion->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::ONION] * 7,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);

        // ingredient inventory
        $this->beef->refresh();
        $this->assertEquals($this->beef->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::BEEF] * 7, $this->beef->inventory);
        $this->cheese->refresh();
        $this->assertEquals($this->cheese->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::CHEESE] * 7, $this->cheese->inventory);
        $this->onion->refresh();
        $this->assertEquals($this->onion->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::ONION] * 7, $this->onion->inventory);
    }

    public function test_create_order_with_different_burgers_different_quantities(): void  // different products
    {
        // == Arrange
        $this->restockIngredients();
        $this->burgerDB();
        $this->bigBurgerDB();

        // == Act
        // create order
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 8
                ],
                [
                    'product_id' => $this->bigBurger->id,
                    'quantity' => 9
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Order created successfully',
        ]);

        // db
        $this->assertDatabaseHas('orders', [
            'id' => 3,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => 3,
            'product_id' => $this->burger->id,
            'quantity' => 8,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => 3,
            'product_id' => $this->bigBurger->id,
            'quantity' => 9,
        ]);

        // ingredient inventory history
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->beef->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] * 8 + Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF] * 9,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->cheese->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE] * 8 + Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE] * 9,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->onion->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::ONION] * 8 + Product::BIG_BURGER_INGREDIENTS[Ingredient::ONION] * 9,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);

        // ingredient inventory
        $this->beef->refresh();
        $this->assertEquals($this->beef->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::BEEF] * 8 - Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF] * 9, $this->beef->inventory);
        $this->cheese->refresh();
        $this->assertEquals($this->cheese->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::CHEESE] * 8 - Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE] * 9, $this->cheese->inventory);
        $this->onion->refresh();
        $this->assertEquals($this->onion->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::ONION] * 8 - Product::BIG_BURGER_INGREDIENTS[Ingredient::ONION] * 9, $this->onion->inventory);
    }

    public function test_create_order_with_multiple_burgers_and_inventory_gets_below_half_email_is_sent(): void
    {
        // == Arrange
        $this->restockIngredients();
        $this->burgerDB();
        Mail::fake();

        // == Act
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 40 // 40*150 = 6000, 40*30 = 1200, 40*20 = 800 - the onion will go below half of its inventory level
                ],
            ]
        ]);

        // == Assert
        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Order created successfully',
        ]);

        // db
        $this->assertDatabaseHas('orders', [
            'id' => 4,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => 4,
            'product_id' => $this->burger->id,
            'quantity' => 40,
        ]);

        // ingredient inventory history
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->beef->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] * 40,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->cheese->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE] * 40,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);
        $this->assertDatabaseHas('ingredient_inventory_histories', [
            'ingredient_id' => $this->onion->id,
            'weight' => Product::BURGER_INGREDIENTS[Ingredient::ONION] * 40,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
        ]);

        // ingredient inventory
        $this->beef->refresh();
        $this->assertEquals($this->beef->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::BEEF] * 40, $this->beef->inventory);
        $this->cheese->refresh();
        $this->assertEquals($this->cheese->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::CHEESE] * 40, $this->cheese->inventory);
        $this->onion->refresh();
        $this->assertEquals($this->onion->inventory_level - Product::BURGER_INGREDIENTS[Ingredient::ONION] * 40, $this->onion->inventory);

        // make sure IngredientInventoryAlert was sent
        Mail::assertSent(IngredientInventoryAlert::class);
        Mail::assertSentCount(1);
    }

    public function test_create_order_with_non_existing_product(): void
    {
        // == Act
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => 'non-existing-product-id',
                    'quantity' => 1
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected product ID is invalid.',
        ]);
    }


    public function test_create_order_with_burger_ingredients_not_enough_single_ingredient(): void
    {
        // == Arrange
        $this->singleBurgerIngredientsInventoryDB();
        $this->burgerDB();

        $this->beef->subtractFromInventory(10);

        // == Act
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 1
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Insufficient inventory for ingredients',
            'ingredients' => [
                [
                    'ingredient_name' => 'Beef',
                    'ingredient_id' => $this->beef->id,
                    'required_amount' => Product::BURGER_INGREDIENTS[Ingredient::BEEF],
                    'available_amount' => 140
                ],
            ]
        ]);
    }

    public function test_create_order_with_burger_ingredients_not_enough_multiple_ingredients(): void
    {
        // == Arrange
        $this->singleBurgerIngredientsInventoryDB();
        $this->bigBurgerDB();

        // == Act
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->bigBurger->id,
                    'quantity' => 1
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Insufficient inventory for ingredients',
            'ingredients' => [
                [
                    'ingredient_name' => Ingredient::BEEF,
                    'ingredient_id' => $this->beef->id,
                    'required_amount' => Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF],
                    'available_amount' => $this->beef->inventory
                ],
                [
                    'ingredient_name' => Ingredient::CHEESE,
                    'ingredient_id' => $this->cheese->id,
                    'required_amount' => Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE],
                    'available_amount' => $this->cheese->inventory
                ],
                [
                    'ingredient_name' => Ingredient::ONION,
                    'ingredient_id' => $this->onion->id,
                    'required_amount' => Product::BIG_BURGER_INGREDIENTS[Ingredient::ONION],
                    'available_amount' => $this->onion->inventory
                ]
            ]
        ]);
    }

    public function test_create_order_with_burger_ingredients_not_enough_single_ingredient_different_burgers(): void // different products
    {
        // == Arrange
        $this->singleBurgerIngredientsInventoryDB();
        $this->singleBigBurgerIngredientsInventoryDB();
        $this->burgerDB();
        $this->bigBurgerDB();

        $this->beef->subtractFromInventory(10);

        // == Act
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 1
                ],
                [
                    'product_id' => $this->bigBurger->id,
                    'quantity' => 1
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Insufficient inventory for ingredients',
            'ingredients' => [
                [
                    'ingredient_name' => Ingredient::BEEF,
                    'ingredient_id' => $this->beef->id,
                    'required_amount' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] + Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF],
                    'available_amount' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] + Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF] - 10
                ],
            ]
        ]);
    }

    public function test_create_order_with_burger_ingredients_not_enough_multiple_ingredients_different_burgers(): void // different products
    {
        // == Arrange
        $this->singleBurgerIngredientsInventoryDB();
        $this->singleBigBurgerIngredientsInventoryDB();
        $this->burgerDB();
        $this->bigBurgerDB();

        $this->beef->subtractFromInventory(10);
        $this->cheese->subtractFromInventory(10);

        // == Act
        $response = $this->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->burger->id,
                    'quantity' => 1
                ],
                [
                    'product_id' => $this->bigBurger->id,
                    'quantity' => 1
                ]
            ]
        ]);

        // == Assert
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Insufficient inventory for ingredients',
            'ingredients' => [
                [
                    'ingredient_name' => Ingredient::BEEF,
                    'ingredient_id' => $this->beef->id,
                    'required_amount' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] + Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF],
                    'available_amount' => Product::BURGER_INGREDIENTS[Ingredient::BEEF] + Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF] - 10
                ],
                [
                    'ingredient_name' => Ingredient::CHEESE,
                    'ingredient_id' => $this->cheese->id,
                    'required_amount' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE] + Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE],
                    'available_amount' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE] + Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE] - 10
                ],
            ]
        ]);
    }

    // == Helpers
    private function ingredientsDB(): void
    {
        $this->beef = Ingredient::factory()->beef()->create();
        $this->cheese = Ingredient::factory()->cheese()->create();
        $this->onion = Ingredient::factory()->onion()->create();
    }

    private function restockIngredients(): void
    {
        $this->beef->restock();
        $this->cheese->restock();
        $this->onion->restock();
    }

    private function singleBurgerIngredientsInventoryDB(): void
    {
        IngredientInventoryHistory::factory()->add()->burgerBeefWeight()->create([
            'ingredient_id' => $this->beef->id,
        ]);
        IngredientInventoryHistory::factory()->add()->burgerCheeseWeight()->create([
            'ingredient_id' => $this->cheese->id,
        ]);
        IngredientInventoryHistory::factory()->add()->burgerOnionWeight()->create([
            'ingredient_id' => $this->onion->id,
        ]);
    }

    private function singleBigBurgerIngredientsInventoryDB(): void
    {
        IngredientInventoryHistory::factory()->add()->bigBurgerBeefWeight()->create([
            'ingredient_id' => $this->beef->id,
        ]);
        IngredientInventoryHistory::factory()->add()->bigBurgerCheeseWeight()->create([
            'ingredient_id' => $this->cheese->id,
        ]);
        IngredientInventoryHistory::factory()->add()->bigBurgerOnionWeight()->create([
            'ingredient_id' => $this->onion->id,
        ]);
    }

    private function burgerDB(): void
    {
        // create burger product
        $this->burger = Product::factory()->burger()->create();

        // create burger ingredients
        $this->burger->ingredients()->attach([
            $this->beef->id => ['ingredient_weight' => Product::BURGER_INGREDIENTS[Ingredient::BEEF]],
            $this->cheese->id => ['ingredient_weight' => Product::BURGER_INGREDIENTS[Ingredient::CHEESE]],
            $this->onion->id => ['ingredient_weight' => Product::BURGER_INGREDIENTS[Ingredient::ONION]]
        ]);
    }

    private function bigBurgerDB(): void
    {
        // create big burger product
        $this->bigBurger = Product::factory()->bigBurger()->create();

        // create big burger ingredients
        $this->bigBurger->ingredients()->attach([
            $this->beef->id => ['ingredient_weight' => Product::BIG_BURGER_INGREDIENTS[Ingredient::BEEF]],
            $this->cheese->id => ['ingredient_weight' => Product::BIG_BURGER_INGREDIENTS[Ingredient::CHEESE]],
            $this->onion->id => ['ingredient_weight' => Product::BIG_BURGER_INGREDIENTS[Ingredient::ONION]]
        ]);
    }
}
