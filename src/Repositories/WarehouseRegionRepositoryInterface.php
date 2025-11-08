<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Molitor\Stock\Models\WarehouseRegion;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRegionRepositoryInterface
{
    public function getAll(): Collection;
    public function delete(WarehouseRegion $warehouseRegion);
}
