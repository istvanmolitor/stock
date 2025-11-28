<?php

namespace Molitor\Stock\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Molitor\Stock\Filament\Resources\WarehouseResource\Pages;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-building-storefront';

    public static function getNavigationGroup(): string
    {
        return __('stock::common.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock::warehouse.title');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('acl', 'stock');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('is_primary')
                ->label(__('stock::common.is_primary')),
            Forms\Components\TextInput::make('name')
                ->label(__('stock::warehouse.form.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label(__('stock::warehouse.form.description')),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_primary')->label(__('stock::common.is_primary'))->boolean(),
                TextColumn::make('name')->label(__('stock::warehouse.table.name'))->searchable()->sortable(),
                TextColumn::make('description')->label(__('stock::warehouse.table.description')),
            ])
            ->actions([
                Action::make('id')
                    ->label(__('stock::common.stock'))
                    ->icon('heroicon-o-cube')
                    ->url(fn (Warehouse $record): string =>
                        ProductResource::getUrl('index', ['warehouse_id' => $record->id])
                    ),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Warehouse $record): bool => $record->is_primary),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
