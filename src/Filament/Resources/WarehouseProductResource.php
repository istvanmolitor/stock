<?php

namespace Molitor\Stock\Filament\Resources;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Molitor\Product\Models\Product;
use Molitor\Stock\Filament\Resources\WarehouseProductResource\Pages;
use Molitor\Stock\Models\Warehouse;

class WarehouseProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-cube';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return Gate::allows('acl', 'stock');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $warehouseId = (int)request()->get('warehouse_id');
        if ($warehouseId <= 0) {
            abort(404);
        }
        return $query
            ->join('stocks AS s', 'products.id', '=', 's.product_id')
            ->join('warehouse_regions as wr', 'wr.id', '=', 's.warehouse_region_id')
            ->join('warehouses as w', 'w.id', '=', 'wr.warehouse_id')
            ->where('w.id', $warehouseId)
            ->select('products.*');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label(__('product::common.sku'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('product::common.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('product::common.price'))
                    ->money(fn ($record) => $record->currency?->code ?? 'HUF')
                    ->sortable(),
                TextColumn::make('warehouseRegions')
                    ->label(__('stock::warehouse_product.table.stock'))
                    ->badge(),
                TextColumn::make('active')
                    ->label(__('product::common.active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('product::common.yes') : __('product::common.no'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->actions([
                Action::make('products')
                    ->label('Termékek')
                    ->icon('heroicon-o-cube')
                    ->url(function ($record) {
                        return 'products?shop_id=' . $record->getKey();
                    }),
            ])
            ->defaultSort('sku')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('product::common.active'))
                    ->trueLabel(__('product::common.active'))
                    ->falseLabel(__('product::common.inactive'))
                    ->queries(
                        true: fn ($query) => $query->where('active', true),
                        false: fn ($query) => $query->where('active', false),
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\WarehouseStockProducts::route('/'),
        ];
    }
}

