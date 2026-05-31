<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateStockProductRegionQuantityLimitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('acl', 'stock');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_quantity' => $this->normalizeNullableNumeric($this->input('min_quantity')),
            'max_quantity' => $this->normalizeNullableNumeric($this->input('max_quantity')),
        ]);
    }

    public function rules(): array
    {
        return [
            'min_quantity' => ['nullable', 'numeric', 'min:0', 'lte:max_quantity'],
            'max_quantity' => ['nullable', 'numeric', 'min:0', 'gte:min_quantity'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_quantity.numeric' => 'A minimum mennyiseg szam legyen.',
            'min_quantity.min' => 'A minimum mennyiseg nem lehet negativ.',
            'min_quantity.lte' => 'A minimum mennyiseg nem lehet nagyobb a maximum mennyisegnel.',
            'max_quantity.numeric' => 'A maximum mennyiseg szam legyen.',
            'max_quantity.min' => 'A maximum mennyiseg nem lehet negativ.',
            'max_quantity.gte' => 'A maximum mennyiseg nem lehet kisebb a minimum mennyisegnel.',
        ];
    }

    private function normalizeNullableNumeric(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }
}

