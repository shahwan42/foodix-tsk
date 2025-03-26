<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getProductsWithIngredients(array $productIds): Collection
    {
        return $this->model->whereIn('id', $productIds)->with('ingredients')->get();
    }
}
