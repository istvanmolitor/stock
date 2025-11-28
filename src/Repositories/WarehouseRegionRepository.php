<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
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

    public function getDefaultWarehouseRegionName(): string
    {
        return 'Régió';
    }

    public function getByName(Warehouse $warehouse, string $name): WarehouseRegion|null
    {
        return $this->warehouseRegion->where('warehouse_id', $warehouse->id)
            ->where('name', $name)
            ->first();
    }

    public function getDefault(Warehouse $warehouse): WarehouseRegion
    {
        $name = $this->getDefaultWarehouseRegionName();
        $defaultRegion = $this->getByName($warehouse, $name);
        if($defaultRegion) {
            return $defaultRegion;
        }
        return $this->warehouseRegion->create([
            'warehouse_id' => $warehouse->id,
            'name' => $name,
        ]);
    }

    public function getDefaultByProduct(Warehouse $warehouse, Product $product): WarehouseRegion
    {
        $region = $this->warehouseRegion->where('warehouse_id', $warehouse->id)
            ->join('stocks', 'stocks.warehouse_region_id', '=', 'warehouse_regions.id')
            ->where('stocks.product_id', $product->id)
            ->orderByDesc('stocks.quantity')->select('warehouse_regions.*')->first();
        if($region) {
            return $region;
        }
        return $this->getDefault($warehouse);
    }

    public function setPrimary(WarehouseRegion $warehouseRegion): void
    {
        $this->warehouseRegion
            ->where('warehouse_id', $warehouseRegion->warehouse_id)
            ->where('id', '<>', $warehouseRegion->id)
            ->update(['is_primary' => false]);
        $warehouseRegion->is_primary = true;
        $warehouseRegion->save();
    }
}
