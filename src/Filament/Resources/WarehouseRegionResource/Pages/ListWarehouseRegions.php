<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource;

class ListWarehouseRegions extends ListRecords
{
    protected static string $resource = WarehouseRegionResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::common.list');
    }

    public function getTitle(): string
    {
        return __('stock::warehouse_region.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('stock::warehouse_region.create'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
