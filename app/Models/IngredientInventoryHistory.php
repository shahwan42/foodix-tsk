<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientInventoryHistory extends Model
{
    /** @use HasFactory<\Database\Factories\IngredientInventoryHistoryFactory> */
    use HasFactory;

    public const EVENT_ADD = 'add';
    public const EVENT_SUBTRACT = 'subtract';

    protected $fillable = [
        'ingredient_id',
        'event',
        'weight',
    ];

    /**
     * The number of seconds to cache the inventory calculation
     */
    private const CACHE_TTL = 300; // 5 minutes

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function ($history) {
            static::forgetInventoryCache($history->ingredient_id);
        });

        static::updated(function ($history) {
            static::forgetInventoryCache($history->ingredient_id);
        });

        static::deleted(function ($history) {
            static::forgetInventoryCache($history->ingredient_id);
        });
    }

    /**
     * Get the cache key for an ingredient's inventory
     */
    private static function getInventoryCacheKey(int $ingredientId): string
    {
        return "ingredient_inventory_{$ingredientId}";
    }

    /**
     * Forget the cached inventory for an ingredient
     */
    private static function forgetInventoryCache(int $ingredientId): void
    {
        cache()->forget(static::getInventoryCacheKey($ingredientId));
    }

    /**
     * Calculate the current inventory level for this ingredient
     */
    public static function inventory(int $ingredientId): int
    {
        $cacheKey = static::getInventoryCacheKey($ingredientId);

        return cache()->remember($cacheKey, static::CACHE_TTL, function () use ($ingredientId) {
            return static::where('ingredient_id', $ingredientId)
                ->selectRaw('SUM(CASE WHEN event = ? THEN weight ELSE -weight END) as inventory', [self::EVENT_ADD])
                ->value('inventory') ?? 0;
        });
    }
}
