<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource;

class InventoryWarehouseRegion extends ManageRelatedRecords
{
    protected static string $resource = WarehouseRegionResource::class;

    protected static string $relationship = 'stocks';

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse_region.stocks.title');
    }

    public function getTitle(): string
    {
        return (string) $this->getOwnerRecord();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product')
                    ->label(__('stock::warehouse_region.stocks.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('stock::warehouse_region.stocks.quantity'))
                    ->numeric(2)
                    ->sortable(),
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([

            ]);
    }
}
