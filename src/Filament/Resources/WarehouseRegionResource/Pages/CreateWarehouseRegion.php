<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource;

class CreateWarehouseRegion extends CreateRecord
{
    protected static string $resource = WarehouseRegionResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse_region.create');
    }

    public function getTitle(): string
    {
        return __('stock::warehouse_region.create');
    }
}
