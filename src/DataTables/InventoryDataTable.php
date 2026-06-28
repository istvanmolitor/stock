<?php

declare(strict_types=1);

namespace Molitor\Stock\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Molitor\Admin\DataTables\DataTable;
use Molitor\Stock\Http\Resources\InventoryResource;
use Molitor\Stock\Models\Inventory;

class InventoryDataTable extends DataTable
{
    protected function getModelClass(): string
    {
        return Inventory::class;
    }

    protected function getResourceClass(): string
    {
        return InventoryResource::class;
    }

    protected function getSearchPlaceholder(): string
    {
        return 'Keresés leírás alapján...';
    }

    protected function initColumns(): void
    {
        $this->addColumn('warehouse_region')->setLabel('Raktárrégió');
        $this->addColumn('user')->setLabel('Felelős');
        $this->addColumn('created_at')->setOrderable();
        $this->addColumn('description')->setSearchable();
        $this->addColumn('is_closed')->setLabel('Állapot');
        $this->addColumn('stock_updated_at')->setLabel('Lezárva')->setOrderable();
    }

    protected function getDefaultSort(): string
    {
        return 'created_at';
    }

    protected function getDefaultDirection(): string
    {
        return 'desc';
    }

    public function query(Builder $query): Builder
    {
        return $query
            ->with(['warehouseRegion.warehouse:id,name', 'user:id,name'])
            ->withCount('inventoryItems');
    }
}
