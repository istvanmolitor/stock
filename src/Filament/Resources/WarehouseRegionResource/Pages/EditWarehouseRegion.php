<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;

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

    protected function afterSave(): void
    {
        /** @var \Molitor\Stock\Models\WarehouseRegion $record */
        $record = $this->record;

        if ($record->is_primary) {
            /** @var WarehouseRegionRepositoryInterface $repository */
            $repository = app(WarehouseRegionRepositoryInterface::class);
            $repository->setPrimary($record);
        }
    }
}
