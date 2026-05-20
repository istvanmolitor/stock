<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Molitor\Stock\Enums\StockMovementType;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(static fn (StockMovementType $type): string => $type->value, StockMovementType::cases()))],
            'warehouse_region_id' => ['required', 'integer', Rule::exists('warehouse_regions', 'id')],
            'destination_warehouse_region_id' => ['nullable', 'required_if:type,transfer', 'integer', 'different:warehouse_region_id', Rule::exists('warehouse_regions', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'A mozgás típusa kötelező.',
            'type.in' => 'A mozgás típusa csak be, ki vagy áthelyezés lehet.',
            'warehouse_region_id.required' => 'A forrás raktárrégió megadása kötelező.',
            'warehouse_region_id.integer' => 'A forrás raktárrégió azonosítója érvényes szám legyen.',
            'warehouse_region_id.exists' => 'A kiválasztott forrás raktárrégió nem létezik.',
            'destination_warehouse_region_id.required_if' => 'Áthelyezés esetén cél raktárrégió megadása kötelező.',
            'destination_warehouse_region_id.integer' => 'A cél raktárrégió azonosítója érvényes szám legyen.',
            'destination_warehouse_region_id.exists' => 'A kiválasztott cél raktárrégió nem létezik.',
            'destination_warehouse_region_id.different' => 'A cél raktárrégió nem egyezhet meg a forrással.',
            'product_id.required' => 'A termék megadása kötelező.',
            'product_id.integer' => 'A termék azonosítója érvényes szám legyen.',
            'product_id.exists' => 'A kiválasztott termék nem létezik.',
            'quantity.required' => 'A mennyiség megadása kötelező.',
            'quantity.integer' => 'A mennyiség csak egész szám lehet.',
            'quantity.min' => 'A mennyiség legalább 1 legyen.',
            'description.string' => 'A leírás szöveg legyen.',
        ];
    }
}

