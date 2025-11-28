<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

interface StockRepositoryInterface
{
    public function getAllQuantity(Product $product): int;

    public function getQuantityByWarehouse(Warehouse $warehouse, Product $product): int;

    public function getQuantity(WarehouseRegion $warehouseRegion, Product $product): int;

    public function exists(WarehouseRegion $warehouseRegion, Product $product): bool;

    public function setQuantity(WarehouseRegion $warehouseRegion, Product $product, int $quantity): void;

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void;
}
