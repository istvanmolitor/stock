<?php

namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Molitor\Stock\Filament\Resources\StockMovementResource;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::common.view');
    }

    public function getTitle(): string
    {
        return __('stock::stock_movement.view_title') . ' #' . ($this->record?->id ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('stock::common.edit'))
                ->visible(fn () => is_null($this->record->closed_at)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make(__('stock::common.basic_info'))
                    ->schema([
                        TextEntry::make('type')
                            ->label(__('stock::common.type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                        TextEntry::make('user.name')
                            ->label(__('stock::common.user')),
                        TextEntry::make('warehouse.name')
                            ->label(__('stock::common.warehouse')),
                        IconEntry::make('is_closed')
                            ->label(__('stock::common.is_closed'))
                            ->boolean()
                            ->state(fn ($record) => !is_null($record->closed_at)),
                    ])
                    ->columns(2),
                Fieldset::make(__('stock::common.linked_stock_movement'))
                    ->schema([
                        TextEntry::make('linked_stock_movement_id')
                            ->label('')
                            ->state(function ($record) {
                                if (empty($record->linked_stock_movement_id)) {
                                    return null;
                                }
                                $url = StockMovementResource::getUrl('view', ['record' => $record->linked_stock_movement_id]);
                                return new HtmlString(view('stock::components.linked-stock-movement', [
                                    'url' => $url,
                                    'id' => $record->linked_stock_movement_id,
                                ])->render());
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->linked_stock_movement_id)),
                Fieldset::make(__('stock::common.description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('stock::common.description'))
                            ->placeholder(__('stock::common.no_description'))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->description)),
                Fieldset::make(__('stock::common.items'))
                    ->schema([
                        RepeatableEntry::make('stockMovementItems')
                            ->label('')
                            ->schema([
                                TextEntry::make('product')
                                    ->label(__('stock::common.product'))
                                    ->state(fn ($record) => (string) $record->product)
                                    ->columnSpan(2),
                                TextEntry::make('quantity')
                                    ->label(__('stock::common.quantity'))
                                    ->state(fn ($record) => $record->quantity . ' ' . ($record->product?->productUnit ? (string) $record->product->productUnit : ''))
                                    ->columnSpan(1),
                                TextEntry::make('warehouseRegion.name')
                                    ->label(__('stock::common.warehouse_region'))
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
                Fieldset::make(__('stock::common.timestamps'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('stock::common.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('stock::common.updated_at'))
                            ->dateTime(),
                        TextEntry::make('closed_at')
                            ->label(__('stock::common.closed_at'))
                            ->dateTime()
                            ->placeholder(__('stock::common.not_closed'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
