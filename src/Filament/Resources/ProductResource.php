<?php

namespace Molitor\Stock\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Gate;
use Molitor\Product\Models\Product;
use Molitor\Stock\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $slug = 'stock/products';

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-cube';

    public static function getNavigationGroup(): string
    {
        return __('stock::common.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock::stock.title');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('acl', 'stock');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->defaultSort('sku')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
        ];
    }
}

