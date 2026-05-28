<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

interface WarehouseRegionRepositoryInterface
{
    public function getAll(): Collection;

    public function findOrFail(int $id): WarehouseRegion;

    public function delete(WarehouseRegion $warehouseRegion);

    public function getByName(Warehouse $warehouse, string $name): ?WarehouseRegion;

    public function getDefault(Warehouse $warehouse): WarehouseRegion;

    public function getDefaultByProduct(Warehouse $warehouse, Product $product): WarehouseRegion;

    public function setPrimary(WarehouseRegion $warehouseRegion): void;
}
