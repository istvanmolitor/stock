<?php

declare(strict_types=1);

namespace Molitor\Stock\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Molitor\Admin\DataTables\DataTable;
use Molitor\Stock\Http\Resources\StockMovementResource;
use Molitor\Stock\Models\StockMovement;

class StockMovementDataTable extends DataTable
{
    protected function getModelClass(): string
    {
        return StockMovement::class;
    }

    protected function getResourceClass(): string
    {
        return StockMovementResource::class;
    }

    protected function getSearchPlaceholder(): string
    {
        return 'Keresés leírás alapján...';
    }

    protected function initColumns(): void
    {
        $this->addColumn('type_label')->setLabel('Típus');
        $this->addColumn('warehouse')->setLabel('Raktár');
        $this->addColumn('created_at')->setOrderable();
        $this->addColumn('description')->setSearchable();
        $this->addColumn('is_closed')->setLabel('Állapot');
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
            ->with(['warehouse:id,name', 'user:id,name'])
            ->withCount('stockMovementItems as items_count');
    }
}
