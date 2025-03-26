<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Models\Order;

class OrderItemRepository extends BaseRepository
{
    public function __construct(OrderItem $model)
    {
        parent::__construct($model);
    }

    public function createOrderItems(Order $order, array $orderProducts): void
    {
        $order->orderItems()->createMany($orderProducts);
    }
}
