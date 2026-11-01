<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:9999'],
            'payment_method' => ['required', Rule::in(enabled_payment_methods())],
            'payment_label' => ['nullable', 'string', 'max:50', 'required_if:payment_method,other'],
            'amount_tendered' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percent']), 'required_with:discount_value'],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'required_with:discount_type'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.product_id.distinct' => 'The same product appears twice. Please refresh the POS and try again.',
            'idempotency_key.required' => 'Please refresh the page and try the sale again.',
        ];
    }
}
