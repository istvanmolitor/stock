<?php

namespace Molitor\Stock\Repositories;

use Illuminate\Support\Collection;
use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

interface StockRepositoryInterface
{
    public function getAllQuantity(Product $product): int;

    public function getQuantityByWarehouse(Warehouse $warehouse, Product $product): int;

    public function getQuantity(WarehouseRegion $warehouseRegion, Product $product): int;

    public function findByWarehouseRegionAndProduct(WarehouseRegion $warehouseRegion, Product $product): ?Stock;

    public function exists(WarehouseRegion $warehouseRegion, Product $product): bool;

    /**
     * @param  array<int, int>  $productIds
     * @return Collection<int, Stock>
     */
    public function getByProductIds(array $productIds): Collection;

    /**
     * @return Collection<int, Stock>
     */
    public function getByWarehouseRegion(WarehouseRegion $warehouseRegion): Collection;

    public function updateValues(WarehouseRegion $warehouseRegion, Product $product, array $values): void;

    public function setQuantity(WarehouseRegion $warehouseRegion, Product $product, int $quantity): void;

    public function setMinQuantity(WarehouseRegion $warehouseRegion, Product $product, int|null $minQuantity): void;

    public function setMaxQuantity(WarehouseRegion $warehouseRegion, Product $product, int|null $maxQuantity): void;

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void;
}
