<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseRegionRepository implements WarehouseRegionRepositoryInterface
{
    private WarehouseRegion $warehouseRegion;

    public function __construct()
    {
        $this->warehouseRegion = new WarehouseRegion();
    }

    public function delete(WarehouseRegion $warehouseRegion)
    {
        $warehouseRegion->delete();
    }

    public function getAll(): Collection
    {
        return $this->warehouseRegion
            ->join('regions', 'regions.id', '=', 'warehouse_regions.region_id')
            ->orderBy(['warehouse.name', 'warehouse_regions.name'])
            ->with('region')
            ->get('warehouse_regions.*');
    }
}
