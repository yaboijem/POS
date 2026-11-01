<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:120'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:50'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', Rule::in(array_keys(payment_method_options()))],
            'tax_enabled' => ['sometimes', 'boolean'],
            'tax_rate' => $this->boolean('tax_enabled')
                ? ['required', 'numeric', 'gt:0', 'max:100']
                : ['nullable', 'numeric'],
            'tax_inclusive' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }
}
