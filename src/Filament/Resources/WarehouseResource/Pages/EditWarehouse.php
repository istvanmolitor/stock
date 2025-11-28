<?php

namespace Molitor\Stock\Filament\Resources\WarehouseResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Molitor\Stock\Filament\Resources\WarehouseResource;
use Molitor\Stock\Repositories\WarehouseRepositoryInterface;

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

    protected function afterSave(): void
    {
        /** @var \Molitor\Stock\Models\Warehouse $record */
        $record = $this->record;

        if ($record->is_primary) {
            /** @var WarehouseRepositoryInterface $repository */
            $repository = app(WarehouseRepositoryInterface::class);
            $repository->setPrimary($record);
        }
    }
}
