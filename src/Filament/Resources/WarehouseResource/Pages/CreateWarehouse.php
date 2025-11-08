<?php

namespace Molitor\Stock\Filament\Resources\WarehouseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Molitor\Stock\Filament\Resources\WarehouseResource;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse.create');
    }

    public function getTitle(): string
    {
        return __('stock::warehouse.create');
    }
}
