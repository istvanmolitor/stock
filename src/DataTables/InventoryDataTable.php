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
        $this->addColumn('created_at')->setOrderable();
        $this->addColumn('description')->setSearchable();
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
