<?php

declare(strict_types=1);

namespace Molitor\Stock\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Molitor\Admin\DataTables\DataTable;
use Molitor\Stock\Http\Resources\WarehouseResource;
use Molitor\Stock\Models\Warehouse;

class WarehouseDataTable extends DataTable
{
    protected function getModelClass(): string
    {
        return Warehouse::class;
    }

    protected function getResourceClass(): string
    {
        return WarehouseResource::class;
    }

    protected function getSearchPlaceholder(): string
    {
        return 'Keresés név vagy leírás alapján...';
    }

    protected function initColumns(): void
    {
        $this->addColumn('name')->setSearchable()->setOrderable();
        $this->addColumn('description')->setSearchable();
    }

    public function query(Builder $query): Builder
    {
        return $query->with('regions');
    }
}
