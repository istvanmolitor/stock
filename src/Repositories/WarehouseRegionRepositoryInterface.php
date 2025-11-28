<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRegionRepositoryInterface
{
    public function getAll(): Collection;
    public function delete(WarehouseRegion $warehouseRegion);

    public function getByName(Warehouse $warehouse, string $name): WarehouseRegion|null;

    public function getDefault(Warehouse $warehouse): WarehouseRegion;

    public function getDefaultByProduct(Warehouse $warehouse, Product $product): WarehouseRegion;

    public function setPrimary(WarehouseRegion $warehouseRegion): void;
}
