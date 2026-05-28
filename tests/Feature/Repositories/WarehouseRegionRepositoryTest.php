<?php

declare(strict_types=1);

namespace Molitor\Stock\Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;
use Tests\TestCase;

class WarehouseRegionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_find_a_warehouse_region_or_fail(): void
    {
        $warehouse = Warehouse::query()->create([
            'name' => 'Központi raktár',
            'description' => null,
            'is_primary' => true,
        ]);

        $warehouseRegion = WarehouseRegion::query()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'A régió',
            'description' => 'Teszt régió',
            'is_primary' => true,
        ]);

        $repository = $this->app->make(WarehouseRegionRepositoryInterface::class);

        $foundWarehouseRegion = $repository->findOrFail($warehouseRegion->id);

        $this->assertSame($warehouseRegion->id, $foundWarehouseRegion->id);
        $this->assertSame($warehouse->id, $foundWarehouseRegion->warehouse_id);
        $this->assertSame('A régió', $foundWarehouseRegion->name);
    }
}

