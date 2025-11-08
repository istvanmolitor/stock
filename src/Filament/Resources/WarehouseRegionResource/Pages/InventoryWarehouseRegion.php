<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
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
                CreateAction::make()
                    ->label(__('stock::warehouse_region.stocks.create'))
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label(__('stock::warehouse_region.stocks.product'))
                            ->relationship('product', 'sku', modifyQueryUsing: function ($query) {
                                $used = $this->getOwnerRecord()->stocks()->pluck('product_id');
                                $query->whereNotIn('id', $used);
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => (string) $record)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('stock::warehouse_region.stocks.quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->required(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['warehouse_region_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('stock::common.edit'))
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('stock::warehouse_region.stocks.quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->required(),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
