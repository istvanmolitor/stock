<?php

namespace Molitor\Stock\Filament\Resources\WarehouseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Molitor\Stock\Filament\Resources\WarehouseResource;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRepositoryInterface;

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

    protected function afterCreate(): void
    {
        /** @var \Molitor\Stock\Models\Warehouse $record */
        $record = $this->record;

        if ($record->is_primary) {
            /** @var WarehouseRepositoryInterface $repository */
            $repository = app(WarehouseRepositoryInterface::class);
            $repository->setPrimary($record);
        }

        /** @var WarehouseRegionRepositoryInterface $regionRepository */
        $regionRepository = app(WarehouseRegionRepositoryInterface::class);
        $defaultRegion = $regionRepository->getDefault($record);
        $regionRepository->setPrimary($defaultRegion);
    }
}
