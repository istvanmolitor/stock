<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegionProduct;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseRegionProductRepository implements WarehouseRegionProductRepositoryInterface
{
    protected WarehouseRegionProduct $warehouseRegionProduct;

    public function __construct()
    {
        $this->warehouseRegionProduct = new WarehouseRegionProduct();
    }

    public function get(WarehouseRegion $warehouseRegion, Product $product): WarehouseRegionProduct|null
    {
        return $this->warehouseRegionProduct->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->first();
    }

    public function set(
        WarehouseRegion $warehouseRegion,
        Product $product,
        ?float $minQuantity,
        ?float $maxQuantity
    ): WarehouseRegionProduct {
        $existing = $this->get($warehouseRegion, $product);

        if ($existing) {
            $existing->update([
                'min_quantity' => $minQuantity,
                'max_quantity' => $maxQuantity,
            ]);
            return $existing;
        }

        return $this->warehouseRegionProduct->create([
            'warehouse_region_id' => $warehouseRegion->id,
            'product_id' => $product->id,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
        ]);
    }

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void
    {
        $this->warehouseRegionProduct->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->delete();
    }

    public function getAllMinQuantity(Product $product): int
    {
        return (int) $this->warehouseRegionProduct
            ->where('product_id', $product->id)
            ->sum('min_quantity');
    }

    public function getMinQuantity(WarehouseRegion $location, Product $product): int
    {
        $record = $this->get($location, $product);

        return (int) ($record->min_quantity ?? 0);
    }

    public function getMinQuantityByWarehouse(Warehouse $location, Product $product): int
    {
        return (int) $this->warehouseRegionProduct
            ->where('product_id', $product->id)
            ->whereHas('warehouseRegion', function ($q) use ($location) {
                $q->where('warehouse_id', $location->id);
            })
            ->sum('min_quantity');
    }
}
