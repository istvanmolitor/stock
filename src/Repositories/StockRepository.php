<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use voku\helper\ASCII;

class StockRepository implements StockRepositoryInterface
{
    protected Stock $stock;

    public function __construct()
    {
        $this->stock = new Stock;
    }

    public function getAllQuantity(Product $product): int
    {
        return (int) $this->stock->where('product_id', $product->id)->sum('quantity');
    }

    public function getQuantityByWarehouse(Warehouse $warehouse, Product $product): int
    {
        return (int) $this->stock->where('warehouse_regions.warehouse_id', $warehouse->id)
            ->join('warehouse_regions', 'warehouse_regions.id', '=', 'stocks.warehouse_region_id')
            ->where('product_id', $product->id)
            ->value('quantity');
    }

    public function getQuantity(WarehouseRegion $warehouseRegion, Product $product): int
    {
        return (int) $this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->value('quantity');
    }

    public function findByWarehouseRegionAndProduct(WarehouseRegion $warehouseRegion, Product $product): ?Stock
    {
        return $this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->first();
    }

    public function exists(WarehouseRegion $warehouseRegion, Product $product): bool
    {
        return (int) $this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function isEmpty(WarehouseRegion $warehouseRegion, Product $product): bool
    {
        $stock = $this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->first();

        if($stock === null) {
            return true;
        }

        return $stock->quantity <= 0 && !$stock->min_quantity && !$stock->max_quantity;
    }

    public function updateValues(WarehouseRegion $warehouseRegion, Product $product, array $values): void
    {
            if ($this->exists($warehouseRegion, $product)) {
                $this->stock->where('warehouse_region_id', $warehouseRegion->id)
                    ->where('product_id', $product->id)
                    ->update($values);
            } else {
                $this->stock->create(array_merge([
                    'warehouse_region_id' => $warehouseRegion->id,
                    'product_id' => $product->id,
                ], $values));
            }
    }


    public function setQuantity(WarehouseRegion $warehouseRegion, Product $product, int $quantity): void
    {
        $this->updateValues($warehouseRegion, $product, ['quantity' => $quantity]);
    }

    public function setMinQuantity(WarehouseRegion $warehouseRegion, Product $product, int|null $minQuantity): void
    {
        $this->updateValues($warehouseRegion, $product, ['min_quantity' => $minQuantity]);
    }

    public function setMaxQuantity(WarehouseRegion $warehouseRegion, Product $product, int|null $maxQuantity): void
    {
        $this->updateValues($warehouseRegion, $product, ['max_quantity' => $maxQuantity]);
    }

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void
    {
        $this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
