<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')],
            'description' => ['nullable', 'string', 'max:2000'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'track_inventory' => ['nullable', 'boolean'],
            'stock_quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999', $this->input('unit') === 'kilogram' ? 'numeric' : 'multiple_of:1'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
