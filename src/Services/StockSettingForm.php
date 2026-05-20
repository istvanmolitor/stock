<?php

namespace Molitor\Stock\Services;

class StockSettingForm
{
    public function getSlug(): string
    {
        return 'stock';
    }

    public function getLabel(): string
    {
        return __('stock::stock.title');
    }

    public function getForm(): array
    {
        return [
            'default_warehouse_name' => __('stock::common.default_warehouse_name'),
            'default_warehouse_region_name' => __('stock::common.default_warehouse_region_name'),
        ];
    }

    public function getDefaultValues(): array
    {
        return [
            'default_warehouse_name' => 'Raktár',
            'default_warehouse_region_name' => 'Régió',
        ];
    }

    public function getFields(): array
    {
        return [
            'default_warehouse_name',
            'default_warehouse_region_name',
        ];
    }
}
