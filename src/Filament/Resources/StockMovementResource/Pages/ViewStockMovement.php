<?php

namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Molitor\Stock\Filament\Resources\StockMovementResource;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::stock_movement.title');
    }

    public function getTitle(): string
    {
        return (string) $this->record?->id ?? '';
    }
}
