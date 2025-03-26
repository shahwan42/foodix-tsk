<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public const BURGER = 'Burger';
    public const BURGER_INGREDIENTS = [
        Ingredient::BEEF => 150,
        Ingredient::CHEESE => 30,
        Ingredient::ONION => 20,
    ];

    public const BIG_BURGER = 'Big Burger';
    public const BIG_BURGER_INGREDIENTS = [
        Ingredient::BEEF => 200,
        Ingredient::CHEESE => 40,
        Ingredient::ONION => 25,
    ];

    protected $fillable = [
        'name',
    ];

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)
            ->withPivot('ingredient_weight');
    }
}
