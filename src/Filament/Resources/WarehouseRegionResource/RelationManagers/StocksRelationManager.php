<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('stock::warehouse_region.stocks.title');
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
                \Filament\Actions\CreateAction::make()
                    ->label(__('stock::warehouse_region.stocks.create') )
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
                        // Ensure warehouse_region_id is set from owner record
                        $data['warehouse_region_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->label(__('stock::common.edit') )
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('stock::warehouse_region.stocks.quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->required(),
                    ]),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
