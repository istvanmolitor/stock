<?php

namespace Molitor\Stock\Filament\Resources\WarehouseRegionResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WarehouseRegionProductRelationManager extends RelationManager
{
    protected static string $relationship = 'warehouseRegionProducts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('stock::common.product_settings');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product')
                    ->label(__('stock::common.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('min_quantity')
                    ->label(__('stock::common.min_quantity'))
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('max_quantity')
                    ->label(__('stock::common.max_quantity'))
                    ->numeric(2)
                    ->sortable(),
            ])
            ->defaultSort('product_id')
            ->headerActions([
                CreateAction::make()
                    ->label(__('stock::common.create'))
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label(__('stock::common.product'))
                            ->relationship('product', 'sku', modifyQueryUsing: function ($query) {
                                $used = $this->getOwnerRecord()->warehouseRegionProducts()->pluck('product_id');
                                $query->whereNotIn('id', $used);
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => (string) $record)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('min_quantity')
                            ->label(__('stock::common.min_quantity'))
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->nullable(),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label(__('stock::common.max_quantity'))
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->nullable(),
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
                        Forms\Components\TextInput::make('min_quantity')
                            ->label(__('stock::common.min_quantity'))
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->nullable(),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label(__('stock::common.max_quantity'))
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->nullable(),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
