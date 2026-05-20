<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A raktár neve kötelező.',
            'name.string' => 'A raktár neve szöveg legyen.',
            'name.max' => 'A raktár neve legfeljebb 255 karakter lehet.',
            'description.string' => 'A raktár leírása szöveg legyen.',
            'is_primary.boolean' => 'Az elsődleges mező értéke igen vagy nem lehet.',
        ];
    }
}

