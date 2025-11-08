<?php

namespace Molitor\Stock\Filament\Resources\WarehouseResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Molitor\Stock\Filament\Resources\WarehouseResource;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse.edit');
    }

    public function getTitle(): string
    {
        return __('stock::warehouse.edit');
    }
}
