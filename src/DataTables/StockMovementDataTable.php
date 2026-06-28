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

    protected function getBaseQuery(): Builder
    {
        return StockMovement::query()
            ->with(['warehouse:id,name', 'user:id,name'])
            ->withCount('stockMovementItems as items_count');
    }
}
