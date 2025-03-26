<?php

namespace App\Http\Requests;

use App\DTO\CreateOrderDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'products.*.product_id.required' => 'The product ID is required.',
            'products.*.product_id.exists' => 'The selected product ID is invalid.',
            'products.*.quantity.required' => 'The quantity is required.',
            'products.*.quantity.integer' => 'The quantity must be an integer.',
            'products.*.quantity.min' => 'The quantity must be at least 1.',
        ];
    }

    public function toDto(): CreateOrderDto
    {
        return new CreateOrderDto($this->validated('products'));
    }
}
