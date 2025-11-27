<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

interface StockRepositoryInterface
{
    public function getQuantity(WarehouseRegion|Warehouse|null $place, Product $product): int|null;

    public function exists(WarehouseRegion $warehouseRegion, Product $product): bool;

    public function setQuantity(WarehouseRegion $warehouseRegion, Product $product, int $quantity): void;

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void;
}
