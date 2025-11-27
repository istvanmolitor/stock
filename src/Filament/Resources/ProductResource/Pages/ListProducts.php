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

    public ?int $warehouseRegionId = null;
    public ?int $warehouseId = null;

    public function mount(): void
    {
        parent::mount();

        $this->warehouseRegionId = request()->integer('warehouse_region_id') ?: null;
        $this->warehouseId = request()->integer('warehouse_id') ?: null;

        $this->loadFilter();
    }

    protected function loadFilter(): void
    {
        if($this->warehouseRegionId) {
            $this->filter = WarehouseRegion::findOrFail($this->warehouseRegionId);
        } elseif($this->warehouseId) {
            $this->filter = Warehouse::findOrFail($this->warehouseId);
        }
    }

    public function getBreadcrumb(): string
    {
        return __('stock::common.list');
    }

    public function getTitle(): string
    {
        if($this->filter instanceof Warehouse) {
            return __('stock::common.warehouse') . ': ' . $this->filter;
        }
        if($this->filter instanceof WarehouseRegion) {
            return __('stock::common.warehouse_region') . ': ' . $this->filter;
        }

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
                    ->label(__('stock::common.quantity'))
                    ->formatStateUsing(function($record) use ($stockRepository) {
                        return $stockRepository->getQuantity($this->filter, $record) . ' ' . $record->productUnit;
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $this->loadFilter();

        $query = parent::getTableQuery();

        if($this->filter) {
            if($this->filter instanceof Warehouse) {
                $subQuery = DB::table('stocks as s')
                    ->join('warehouse_regions as wr', 'wr.id', '=', 's.warehouse_region_id')
                    ->where('wr.warehouse_id', $this->filter->id)
                    ->select('s.product_id');
            } elseif($this->filter instanceof WarehouseRegion) {
                $subQuery = DB::table('stocks as s')->where('s.warehouse_region_id', $this->filter->id)
                    ->select('s.product_id');
            }
            return $query->joinSub($subQuery, 'sub', 'products.id', '=', 'sub.product_id');
        }

        return $query;
    }
}

