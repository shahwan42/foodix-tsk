<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\IngredientRepository;
use App\DTO\CreateOrderDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Repositories\OrderItemRepository;
use App\Util\Response;

class OrderService
{
    private ProductRepository $productRepository;
    private IngredientRepository $ingredientRepository;
    private OrderRepository $orderRepository;
    private OrderItemRepository $orderItemRepository;
    public function __construct(
        public readonly CreateOrderDto $dto,
    ) {
    }

    public static function init(CreateOrderDto $dto): OrderService
    {
        $orderService = new OrderService($dto);
        $orderService->productRepository = app(ProductRepository::class);
        $orderService->ingredientRepository = app(IngredientRepository::class);
        $orderService->orderRepository = app(OrderRepository::class);
        $orderService->orderItemRepository = app(OrderItemRepository::class);
        return $orderService;
    }

    public function createOrder()
    {
        // ========== inventory check ==========
        $products = $this->productRepository->getProductsWithIngredients($this->dto->productIds());

        $requiredIngredients = $this->getRequiredIngredients($products);

        $insufficientIngredients = $this->checkInventory($requiredIngredients);
        if (!empty($insufficientIngredients)) {
            return response()->json([
                'message' => 'Insufficient inventory for ingredients',
                'ingredients' => $insufficientIngredients
            ], 400);
        }

        // ========== order creation ==========
        DB::beginTransaction();
        try {
            // Create order
            /* @var Order $order */
            $order = $this->orderRepository->create();

            // Create order items
            $this->orderItemRepository->createOrderItems($order, $this->dto->products);

            // Subtract total amounts from ingredients
            $this->ingredientRepository->subtractFromInventory($requiredIngredients);

            DB::commit();

            return Response::init()
                ->created()
                ->message('Order created successfully')
                ->result((new OrderResource($order->load('orderItems')))->toArray(request()))
                ->toResponse();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Could not create a new order', ['errMsg' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'products' => $this->dto->products, 'requiredIngredients' => $requiredIngredients]);

            return Response::init()
                ->badRequest()
                ->message('Unexpected error occurred during order creation. I\'s been reported and w\'re working on it. Please try again. If the error persists, please don\'t hesitate to contact us. Thank you for using our platform.')
                ->toResponse();
        }
    }

    private function getRequiredIngredients(Collection $products): array
    {
        // Check total required ingredients across all products
        $requiredIngredients = [];

        // Calculate total required amount for each ingredient
        foreach ($products as $product) {
            $quantity = collect($this->dto->products)->firstWhere('product_id', $product->id)['quantity'];

            foreach ($product->ingredients as $ingredient) {
                $requiredAmount = $ingredient->pivot->ingredient_weight * $quantity;

                if (!isset($requiredIngredients[$ingredient->id])) {
                    $requiredIngredients[$ingredient->id] = [
                        'name' => $ingredient->name,
                        'required' => 0,
                        'available' => $ingredient->inventory
                    ];
                }
                $requiredIngredients[$ingredient->id]['required'] += $requiredAmount;
            }
        }

        return $requiredIngredients;
    }

    private function checkInventory(array $requiredIngredients): array
    {

        // Check if we have enough of each ingredient for all products
        $insufficientIngredients = [];
        foreach ($requiredIngredients as $ingredientId => $data) {
            if ($data['required'] > $data['available']) {
                $insufficientIngredients[] = [
                    'ingredient_name' => $data['name'],
                    'ingredient_id' => $ingredientId,
                    'required_amount' => $data['required'],
                    'available_amount' => $data['available']
                ];
            }
        }

        return $insufficientIngredients;
    }
}
