<?php

namespace Molitor\Stock\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Gate;
use Molitor\Product\Models\Product;
use Molitor\Stock\Filament\Resources\StockMovementResource\Pages;
use Molitor\Stock\Models\StockMovement;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-receipt-refund';

    public static function getNavigationGroup(): string
    {
        return __('stock::common.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock::stock_movement.title');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('acl', 'stock_movement');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Select::make('type')
                    ->label(__('stock::common.type'))
                    ->options([
                        'in' => __('stock::common.type_in'),
                        'out' => __('stock::common.type_out'),
                        'transfer' => __('stock::common.type_transfer'),
                    ])
                    ->required(),
                Select::make('warehouse_id')
                    ->label(__('stock::common.warehouse'))
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Reset warehouse_region_id in all repeater items when warehouse changes
                        $set('stockMovementItems', []);
                    }),
            ]),
            Textarea::make('description')
                ->label(__('stock::common.description'))
                ->columnSpanFull(),
            Repeater::make('stockMovementItems')
                ->relationship()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('product_id')
                            ->label(__('purchase::common.product'))
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn($record) => (string) $record)
                            ->relationship('product', 'id')
                            ->required()
                            ->reactive()
                            ->columnSpan(1),
                        TextInput::make('quantity')
                            ->label(__('purchase::common.quantity'))
                            ->numeric()
                            ->required()
                            ->columnSpan(1)
                            ->suffix(function ($state, callable $get) {
                                $productId = $get('product_id');
                                if (empty($productId)) {
                                    return null;
                                }
                                try {
                                    $product = Product::with('productUnit')->find($productId);
                                } catch (\Throwable $e) {
                                    return null;
                                }
                                if (! $product || ! $product->productUnit) {
                                    return null;
                                }
                                return (string) $product->productUnit;
                            }),
                        Select::make('warehouse_region_id')
                            ->label(__('stock::common.warehouse_region'))
                            ->relationship('warehouseRegion', 'name', function ($query, callable $get) {
                                $warehouseId = $get('../../warehouse_id');
                                if ($warehouseId) {
                                    $query->where('warehouse_id', $warehouseId);
                                }
                                else {
                                    $query->whereRaw('1 = 0'); // No results
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),
                ])
                ->columns(1)
                ->defaultItems(1)
                ->addActionLabel(__('stock::common.add_item')),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('stock::common.code'))->searchable()->sortable(),
                TextColumn::make('customer.name')->label(__('stock::common.customer'))->sortable(),
                TextColumn::make('orderStatus')->label(__('stock::common.status')),
            ])
            ->filters([
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'edit' => Pages\EditStockMovement::route('/{record}/edit'),
        ];
    }
}
