<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_region_id' => ['required', 'integer', Rule::exists('warehouse_regions', 'id')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_region_id.required' => 'A régió kiválasztása kötelező.',
            'warehouse_region_id.integer' => 'A régió azonosítója csak szám lehet.',
            'warehouse_region_id.exists' => 'A kiválasztott régió nem létezik.',
            'description.string' => 'A leírás csak szöveg lehet.',
        ];
    }
}

