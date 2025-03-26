<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Jobs\IngredientInventoryAlertJob;

class Ingredient extends Model
{
    /** @use HasFactory<\Database\Factories\IngredientFactory> */
    use HasFactory;

    # ingredients available
    public const BEEF = 'Beef';
    public const CHEESE = 'Cheese';
    public const ONION = 'Onion';

    public const INVENTORY_LEVEL = [
        self::BEEF => 20_000, # 20kg
        self::CHEESE => 5_000, # 5kg
        self::ONION => 1_000, # 1kg
    ];

    protected $fillable = [
        'name',
    ];

    public function inventoryHistory(): HasMany
    {
        return $this->hasMany(IngredientInventoryHistory::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    #[Attribute]
    public function inventory(): Attribute
    {
        return new Attribute(
            get: fn () => IngredientInventoryHistory::inventory($this->id)
        );
    }

    public function subtractFromInventory(int $weight): void
    {
        // guard against negative inventory
        if ($this->inventory - $weight < 0) {
            throw new \Exception('Inventory cannot be negative');
        }

        // create inventory history
        IngredientInventoryHistory::create([
            'ingredient_id' => $this->id,
            'event' => IngredientInventoryHistory::EVENT_SUBTRACT,
            'weight' => $weight,
        ]);

        // send email if inventory is below half of the inventory level
        if ($this->inventory <= $this->inventory_level / 2.0) {
            if (!$this->alert_sent_at) {
                // send email
                IngredientInventoryAlertJob::dispatch(
                    ['ingredient_id' => $this->id, 'ingredient_name' => $this->name, 'inventory_level' => $this->inventory_level, 'inventory' => $this->inventory]
                )->onQueue('high');
                $this->alert_sent_at = now();
                $this->save();
            }
        }
    }

    public function addToInventory(int $weight): void
    {
        // guard against inventory exceeding inventory level
        if ($this->inventory + $weight > $this->inventory_level) {
            throw new \Exception('Inventory cannot exceed inventory level');
        }

        // create inventory history
        IngredientInventoryHistory::create([
            'ingredient_id' => $this->id,
            'event' => IngredientInventoryHistory::EVENT_ADD,
            'weight' => $weight,
        ]);

        // reset notification flag
        $this->alert_sent_at = null;
        $this->save();
    }

    public function restock(): void
    {
        $this->addToInventory($this->inventory_level);
    }
}
