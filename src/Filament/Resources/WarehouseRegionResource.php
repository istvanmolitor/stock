<?php

namespace Molitor\Stock\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Molitor\Stock\Filament\Resources\WarehouseRegionResource\Pages;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseRegionResource extends Resource
{
    protected static ?string $model = WarehouseRegion::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-map-pin';

    public static function getNavigationGroup(): string
    {
        return __('stock::common.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock::warehouse_region.title');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('acl', 'stock');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('warehouse_id')
                ->label(__('stock::warehouse_region.form.warehouse'))
                ->relationship('warehouse', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Toggle::make('is_primary')
                ->label(__('stock::common.is_primary')),
            Forms\Components\TextInput::make('name')
                ->label(__('stock::warehouse_region.form.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label(__('stock::warehouse_region.form.description')),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_primary')->label(__('stock::common.is_primary'))->boolean(),
                TextColumn::make('warehouse.name')->label(__('stock::warehouse_region.table.warehouse'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('stock::warehouse_region.table.name'))->searchable()->sortable(),
                TextColumn::make('description')->label(__('stock::warehouse_region.table.description')),
            ])
            ->actions([
                Action::make('stock')
                    ->label(__('stock::warehouse_region.stocks.title'))
                    ->icon('heroicon-o-cube')
                    ->url(fn (WarehouseRegion $record): string =>
                        ProductResource::getUrl('index', ['warehouse_region_id' => $record->id])
                    ),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (WarehouseRegion $record): bool => $record->is_primary),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseRegions::route('/'),
            'create' => Pages\CreateWarehouseRegion::route('/create'),
            'edit' => Pages\EditWarehouseRegion::route('/{record}/edit'),
        ];
    }
}
