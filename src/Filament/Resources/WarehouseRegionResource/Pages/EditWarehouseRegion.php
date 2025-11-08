<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource;

class EditWarehouseRegion extends EditRecord
{
    protected static string $resource = WarehouseRegionResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse_region.edit');
    }

    public function getTitle(): string
    {
        return __('stock::warehouse_region.edit');
    }
}
