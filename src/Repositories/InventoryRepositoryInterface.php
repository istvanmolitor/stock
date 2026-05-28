<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Molitor\Stock\Models\Inventory;
use Molitor\Stock\Models\WarehouseRegion;

interface InventoryRepositoryInterface
{
    public function create(WarehouseRegion $warehouseRegion, ?string $description = null): Inventory;
}

