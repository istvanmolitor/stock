<?php

declare(strict_types=1);

namespace Molitor\Stock\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Molitor\Admin\DataTables\DataTable;
use Molitor\Stock\Http\Resources\WarehouseRegionResource;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseRegionDataTable extends DataTable
{
    protected function getModelClass(): string
    {
        return WarehouseRegion::class;
    }

    protected function getResourceClass(): string
    {
        return WarehouseRegionResource::class;
    }

    protected function getSearchPlaceholder(): string
    {
        return 'Keresés név vagy leírás alapján...';
    }

    protected function initColumns(): void
    {
        $this->addColumn('name')->setSearchable()->setOrderable();
        $this->addColumn('warehouse')->setLabel('Raktár');
        $this->addColumn('description')->setSearchable();
        $this->addColumn('is_primary')->setLabel('Elsődleges')->setOrderable();
    }

    public function query(Builder $query): Builder
    {
        return $query->with('warehouse');
    }
}
