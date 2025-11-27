<?php

namespace Molitor\Stock\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Molitor\Stock\Filament\Resources\ProductResource;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\StockRepositoryInterface;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected Warehouse|WarehouseRegion|null $filter = null;

    public function mount(): void
    {
        parent::mount();

        $warehouseRegionId = request()->integer('warehouse_region_id');
        if($warehouseRegionId) {
            $this->filter = WarehouseRegion::findOrFail($warehouseRegionId);
        }
        $warehouseId = request()->integer('warehouse_id');
        if($warehouseId) {
            $this->filter = Warehouse::findOrFail($warehouseId);
        }
    }

    public function getBreadcrumb(): string
    {
        return __('stock::common.list');
    }

    public function getTitle(): string
    {
        return __('stock::stock.title');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        /** @var StockRepositoryInterface $stockRepositoryInterface */
        $stockRepository = app(StockRepositoryInterface::class);

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('mainImage.image')
                    ->label(__('product::common.image'))
                    ->size(100)
                    ->disk('public')
                    ->toggleable(),
                TextColumn::make('sku')
                    ->label(__('stock::product.table.sku'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('translation.name')
                    ->label(__('stock::product.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('id')
                    ->label(__('stock::product.table.id_hash'))
                    ->formatStateUsing(function($state, $record) use ($stockRepository) {
                        return $stockRepository->getQuantity($this->filter, $record);
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if($this->filter) {
            if($this->filter instanceof Warehouse) {
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('stocks')
                        ->join('warehouse_regions as wr', 'wr.id', '=', 'stocks.warehouse_region_id')
                        ->whereColumn('stocks.product_id', 'products.id')
                        ->where('wr.warehouse_id', $this->filter->id);
                });
            } elseif($this->filter instanceof WarehouseRegion) {
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('stocks')
                        ->whereColumn('stocks.product_id', 'products.id')
                        ->where('stocks.warehouse_region_id', $this->filter->id);
                });
            }
        }

        return $query;
    }
}

