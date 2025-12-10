<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\WarehouseRegionProductSetting;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseRegionProductRepository implements StockThreswarehouseRegionRepositoryInterface
{
    protected WarehouseRegionProductSetting $model;

    public function __construct()
    {
        $this->model = new WarehouseRegionProductSetting();
    }

    public function get(WarehouseRegion $warehouseRegion, Product $product): ?WarehouseRegionProductSetting
    {
        return $this->model->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->first();
    }

    public function set(
        WarehouseRegion $warehouseRegion,
        Product $product,
        ?float $minQuantity,
        ?float $maxQuantity
    ): WarehouseRegionProductSetting {
        $existing = $this->get($warehouseRegion, $product);

        if ($existing) {
            $existing->update([
                'min_quantity' => $minQuantity,
                'max_quantity' => $maxQuantity,
            ]);
            return $existing;
        }

        return $this->model->create([
            'warehouse_region_id' => $warehouseRegion->id,
            'product_id' => $product->id,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
        ]);
    }

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void
    {
        $this->model->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
