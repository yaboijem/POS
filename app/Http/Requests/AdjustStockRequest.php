<?php

namespace App\Http\Requests;

use App\Models\InventoryMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdjustStockRequest extends FormRequest
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
            'type' => ['required', Rule::in([InventoryMovement::TYPE_STOCK_IN, InventoryMovement::TYPE_STOCK_OUT, InventoryMovement::TYPE_ADJUSTMENT])],
            'quantity' => ['required', 'numeric', 'min:0', 'max:999999'],
            'reason' => [
                'nullable',
                'string',
                'max:200',
                Rule::requiredIf(fn () => in_array($this->input('type'), [InventoryMovement::TYPE_STOCK_OUT, InventoryMovement::TYPE_ADJUSTMENT], true)),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('type') !== InventoryMovement::TYPE_ADJUSTMENT && (float) $this->input('quantity') <= 0) {
                    $validator->errors()->add('quantity', 'Enter a quantity greater than zero.');
                }
            },
        ];
    }
}
