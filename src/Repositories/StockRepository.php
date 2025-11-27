<?php

namespace Molitor\Stock\Repositories;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

class StockRepository implements StockRepositoryInterface
{
    protected Stock $stock;

    public function __construct()
    {
        $this->stock = new Stock();
    }

    public function getQuantity(WarehouseRegion|Warehouse|null $location, Product $product): int
    {
        if($location instanceof Warehouse) {
            return (int)$this->stock->where('warehouse_regions.warehouse_id', $location->id)
                ->join('warehouse_regions', 'warehouse_regions.id', '=', 'stocks.warehouse_region_id')
                ->where('product_id', $product->id)
                ->value('quantity');
        }

        if($location instanceof WarehouseRegion) {
            return (int)$this->stock->where('warehouse_region_id', $location->id)
                ->where('product_id', $product->id)
                ->value('quantity');
        }
        return 0;
    }

    public function exists(WarehouseRegion $warehouseRegion, Product $product): bool
    {
        return (int)$this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function setQuantity(WarehouseRegion $warehouseRegion, Product $product, int $quantity): void
    {
        if($quantity <= 0) {
            $this->delete($warehouseRegion, $product);
        }
        elseif($this->exists($warehouseRegion, $product)) {
            $this->stock->where('warehouse_region_id', $warehouseRegion->id)
                ->where('product_id', $product->id)
                ->update([
                    'quantity' => $quantity,
                ]);
        }
        else {
            $this->stock->create([
                'warehouse_region_id' => $warehouseRegion->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }
    }

    public function delete(WarehouseRegion $warehouseRegion, Product $product): void
    {
        $this->stock->where('warehouse_region_id', $warehouseRegion->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
