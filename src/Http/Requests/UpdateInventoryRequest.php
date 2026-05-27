<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.new_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'A leírás csak szöveg lehet.',
            'items.required' => 'Legalább egy tétel megadása kötelező.',
            'items.array' => 'A tételek formátuma nem megfelelő.',
            'items.min' => 'Legalább egy tétel megadása kötelező.',
            'items.*.id.integer' => 'A tétel azonosítója csak szám lehet.',
            'items.*.id.exists' => 'A megadott tétel nem található.',
            'items.*.product_id.required' => 'A termék kiválasztása kötelező.',
            'items.*.product_id.integer' => 'A termék azonosítója csak szám lehet.',
            'items.*.product_id.exists' => 'A kiválasztott termék nem található.',
            'items.*.new_quantity.required' => 'Az új készletérték kötelező.',
            'items.*.new_quantity.integer' => 'Az új készletérték csak egész szám lehet.',
            'items.*.new_quantity.min' => 'Az új készletérték nem lehet negatív.',
        ];
    }
}



