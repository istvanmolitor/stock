<?php

namespace Molitor\Stock\Filament\Resources\WarehouseProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Molitor\Stock\Filament\Resources\WarehouseProductResource;
use Molitor\Stock\Models\Warehouse;

class WarehouseStockProducts extends ListRecords
{
    protected static string $resource = WarehouseProductResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::common.list');
    }

    public function getTitle(): string
    {
        return __('stock::stock.product_list');
    }

    public function mount(): void
    {
        if(!request()->has('warehouse_id') or !Warehouse::find(request('warehouse_id'))) {
            abort(404);
        }

        parent::mount();
    }
}

