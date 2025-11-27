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
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Gate;
use Molitor\Product\Models\Product;
use Molitor\Stock\Filament\Resources\StockMovementResource\Pages;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Enums\StockMovementType;

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

    public static function canEdit(Model $record): bool
    {
        return is_null($record->closed_at);
    }

    public static function canDelete(Model $record): bool
    {
        return is_null($record->closed_at);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Select::make('type')
                    ->label(__('stock::common.type'))
                    ->options(StockMovementType::options())
                    ->required()
                    ->disabled(function (callable $get, ?Model $record) {
                        $linked = $get('linked_stock_movement_id');
                        return ! empty($linked) || ! is_null($record?->linked_stock_movement_id);
                    }),
                Select::make('warehouse_id')
                    ->label(__('stock::common.warehouse'))
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('stockMovementItems', []);
                    }),
                Placeholder::make('linked_stock_movement_link')
                    ->label(__('stock::common.linked_stock_movement'))
                    ->content(function (callable $get, ?Model $record) {
                        $linkedId = $get('linked_stock_movement_id') ?? $record?->linked_stock_movement_id;
                        if (empty($linkedId)) {
                            return null;
                        }
                        $url = Pages\ViewStockMovement::getUrl(['record' => $linkedId]);
                        return new HtmlString(view('stock::components.linked-stock-movement', [
                            'url' => $url,
                            'id' => $linkedId,
                        ])->render());
                    })
                    ->visible(function (callable $get, ?Model $record) {
                        $linkedId = $get('linked_stock_movement_id') ?? $record?->linked_stock_movement_id;
                        return ! empty($linkedId);
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
                                    $query->whereRaw('1 = 0');
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),
                ])
                ->columns(1)
                ->required()
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel(__('stock::common.add_item')),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('stock::common.type'))
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof \BackedEnum) {
                            return method_exists($state, 'label') ? $state->label() : $state->value;
                        }
                        try {
                            return StockMovementType::from($state)->label();
                        } catch (\Throwable $e) {
                            return (string) $state;
                        }
                    })
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Felhasználó')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label(__('stock::common.warehouse'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('stock::common.description'))
                    ->limit(50)
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_closed')
                    ->label('Lezárva')
                    ->boolean()
                    ->state(fn (StockMovement $record) => ! is_null($record->closed_at)),
                TextColumn::make('closed_at')
                    ->label(__('stock::common.closed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn (StockMovement $record) => !is_null($record->closed_at)),
                DeleteAction::make()
                    ->hidden(fn (StockMovement $record) => !is_null($record->closed_at)),
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
            'edit' => Pages\EditStockMovement::route('/{record}/edit'),
        ];
    }
}
