<?php

namespace App\DTO;

class CreateOrderDto
{
    public function __construct(
        public readonly array $products,
    ) {
    }

    public function productIds(): array
    {
        return array_column($this->products, 'product_id');
    }
}
