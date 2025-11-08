<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource;

class ViewWarehouseRegion extends ViewRecord
{
    protected static string $resource = WarehouseRegionResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse_region.title');
    }

    public function getTitle(): string
    {
        return (string) $this->record;
    }
}
