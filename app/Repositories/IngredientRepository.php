<?php

namespace App\Repositories;

use App\Models\Ingredient;

class IngredientRepository extends BaseRepository
{
    public function __construct(Ingredient $model)
    {
        parent::__construct($model);
    }

    public function subtractFromInventory(array $requiredIngredients): void
    {
        $this->model->whereIn('id', array_keys($requiredIngredients))
            ->get()
            ->each(function (Ingredient $ingredient) use ($requiredIngredients) {
                $ingredient->subtractFromInventory($requiredIngredients[$ingredient->id]['required']);
            });
    }
}
