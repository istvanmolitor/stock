<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Models\WarehouseRegionProduct;

interface WarehouseRegionProductRepositoryInterface
{
    public function get(WarehouseRegion $warehouseRegion, Product $product): ?WarehouseRegionProduct;

    public function set(
        WarehouseRegion $warehouseRegion,
        Product $product,
        ?float $minQuantity,
        ?float $maxQuantity
    ): WarehouseRegionProduct;

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void;

    public function getAllMinQuantity(Product $product): int;

    public function getMinQuantity(WarehouseRegion $location, Product $product): int;

    public function getMinQuantityByWarehouse(Warehouse $location, Product $product): int;
}
