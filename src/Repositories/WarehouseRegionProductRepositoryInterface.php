<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
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
}
