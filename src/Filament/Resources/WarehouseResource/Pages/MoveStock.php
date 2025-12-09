<?php

namespace Molitor\Stock\Filament\Resources\WarehouseResource\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Molitor\Product\Models\Product;
use Molitor\Stock\Filament\Resources\WarehouseResource;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;
use Molitor\Stock\Services\StockService;

class MoveStock extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = WarehouseResource::class;

    public Warehouse $record;

    public ?array $data = [];

    public function getView(): string
    {
        return 'stock::filament.pages.move-stock';
    }

    public function mount(Warehouse $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }

    public function getBreadcrumb(): string
    {
        return __('stock::warehouse.move_stock');
    }

    public function getTitle(): string
    {
        return __('stock::warehouse.move_stock');
    }


    public function form(Schema $schema): Schema
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->record;

        return $schema
            ->components([
                Select::make('source_region_id')
                    ->label(__('stock::warehouse.form.source_region'))
                    ->required()
                    ->options(
                        $warehouse->regions()
                            ->get()
                            ->map(fn (WarehouseRegion $region) => [
                                'id' => $region->id,
                                'name' => $region->name,
                            ])
                            ->pluck('name', 'id')
                    )
                    ->reactive()
                    ->searchable(),

                Select::make('destination_region_id')
                    ->label(__('stock::warehouse.form.destination_region'))
                    ->required()
                    ->options(
                        $warehouse->regions()
                            ->get()
                            ->map(fn (WarehouseRegion $region) => [
                                'id' => $region->id,
                                'name' => $region->name,
                            ])
                            ->pluck('name', 'id')
                    )
                    ->reactive()
                    ->searchable()
                    ->different('source_region_id')
                    ->helperText(__('stock::warehouse.form.destination_region_helper')),

                Select::make('product_id')
                    ->label(__('stock::warehouse.form.product'))
                    ->required()
                    ->options(
                        Product::query()
                            ->get()
                            ->map(fn (Product $product) => [
                                'id' => $product->id,
                                'name' => $product->name,
                            ])
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->reactive(),

                TextInput::make('quantity')
                    ->label(__('stock::warehouse.form.quantity'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->suffix(function (callable $get) {
                        if (!$get('source_region_id') || !$get('product_id')) {
                            return '';
                        }

                        $sourceRegion = WarehouseRegion::find($get('source_region_id'));
                        $product = Product::find($get('product_id'));

                        if (!$sourceRegion || !$product) {
                            return '';
                        }

                        $stockService = app(StockService::class);
                        $availableStock = $stockService->getStock($sourceRegion, $product);

                        return __('stock::warehouse.form.available_stock', ['stock' => $availableStock]);
                    }),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $sourceRegion = WarehouseRegion::findOrFail($data['source_region_id']);
        $destinationRegion = WarehouseRegion::findOrFail($data['destination_region_id']);
        $product = Product::findOrFail($data['product_id']);
        $quantity = (int) $data['quantity'];

        /** @var StockService $stockService */
        $stockService = app(StockService::class);

        // Check if there is enough stock in the source region
        $availableStock = $stockService->getStock($sourceRegion, $product);

        if ($availableStock < $quantity) {
            Notification::make()
                ->title(__('stock::warehouse.notifications.insufficient_stock_title'))
                ->body(__('stock::warehouse.notifications.insufficient_stock_body', [
                    'available' => $availableStock,
                    'requested' => $quantity
                ]))
                ->danger()
                ->send();

            return;
        }

        // Move the stock
        $stockService->moveStock($sourceRegion, $destinationRegion, $product, $quantity);

        Notification::make()
            ->title(__('stock::warehouse.notifications.stock_moved_title'))
            ->body(__('stock::warehouse.notifications.stock_moved_body', [
                'quantity' => $quantity,
                'product' => $product->name,
                'source' => $sourceRegion->name,
                'destination' => $destinationRegion->name
            ]))
            ->success()
            ->send();

        // Reset the form
        $this->form->fill();
    }
}

