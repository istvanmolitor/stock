<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'A telephely megadása kötelező.',
            'warehouse_id.integer' => 'A telephely azonosítója érvényes szám legyen.',
            'warehouse_id.exists' => 'A kiválasztott telephely nem létezik.',
            'name.required' => 'A régió neve kötelező.',
            'name.string' => 'A régió neve szöveg legyen.',
            'name.max' => 'A régió neve legfeljebb 255 karakter lehet.',
            'description.string' => 'A régió leírása szöveg legyen.',
            'is_primary.boolean' => 'Az elsődleges mező értéke igen vagy nem lehet.',
        ];
    }
}
