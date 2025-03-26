<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Models\Ingredient;
use App\Mail\IngredientInventoryAlert;

class IngredientInventoryAlertJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private array $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // send email to 'inventory_admin@example.com' with subject 'Ingredient Inventory Alert' and body 'The ingredient ' . $ingredient->name . ' is below half of its inventory level.'
        Mail::to('inventory_admin@example.com')->send(new IngredientInventoryAlert($this->data));
    }
}
