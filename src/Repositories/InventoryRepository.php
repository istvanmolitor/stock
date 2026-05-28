<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Molitor\Stock\Models\Inventory;
use Molitor\Stock\Models\InventoryItem;
use Molitor\Stock\Models\WarehouseRegion;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function __construct(
        private Inventory $inventory,
        private StockRepositoryInterface $stockRepository,
    ) {}

    public function create(WarehouseRegion $warehouseRegion, ?string $description = null): Inventory
    {
        /** @var Inventory $inventory */
        $inventory = $this->inventory->newQuery()->create([
            'warehouse_region_id' => $warehouseRegion->id,
            'description' => $description,
        ]);

        $regionStocks = $this->stockRepository->getByWarehouseRegion($warehouseRegion);

        foreach ($regionStocks as $stock) {
            if ((float) $stock->quantity <= 0) {
                continue;
            }

            InventoryItem::query()->create([
                'inventory_id' => $inventory->id,
                'product_id' => $stock->product_id,
                'old_quantity' => null,
                'new_quantity' => (float) $stock->quantity,
            ]);
        }

        return $inventory;
    }
}

