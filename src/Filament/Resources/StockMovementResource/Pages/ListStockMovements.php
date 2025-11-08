<?php
namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Molitor\Stock\Filament\Resources\StockMovementResource;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::common.list');
    }

    public function getTitle(): string
    {
        return __('stock::stock_movement.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('stock::stock_movement.create'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
