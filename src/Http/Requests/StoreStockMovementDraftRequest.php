<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Molitor\Stock\Enums\StockMovementType;

class StoreStockMovementDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(static fn (StockMovementType $type): string => $type->value, StockMovementType::cases()))],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.warehouse_region_id' => ['required', 'integer', Rule::exists('warehouse_regions', 'id')],
            'items.*.destination_warehouse_region_id' => ['nullable', 'integer', 'different:items.*.warehouse_region_id', Rule::exists('warehouse_regions', 'id')],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'A mozgás típusa kötelező.',
            'type.in' => 'A mozgás típusa csak be, ki vagy áthelyezés lehet.',
            'items.required' => 'Legalább egy tételt meg kell adni.',
            'items.min' => 'Legalább egy tételt meg kell adni.',
            'items.*.warehouse_region_id.required' => 'A forrás raktárrégió megadása kötelező.',
            'items.*.warehouse_region_id.exists' => 'A kiválasztott forrás raktárrégió nem létezik.',
            'items.*.destination_warehouse_region_id.exists' => 'A kiválasztott cél raktárrégió nem létezik.',
            'items.*.destination_warehouse_region_id.different' => 'A cél raktárrégió nem egyezhet meg a forrással.',
            'items.*.product_id.required' => 'A termék megadása kötelező.',
            'items.*.product_id.exists' => 'A kiválasztott termék nem létezik.',
            'items.*.quantity.required' => 'A mennyiség megadása kötelező.',
            'items.*.quantity.min' => 'A mennyiségnek 0-nál nagyobbnak kell lennie.',
        ];
    }
}
