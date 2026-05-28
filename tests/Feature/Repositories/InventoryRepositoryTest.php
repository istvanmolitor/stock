<?php

declare(strict_types=1);

namespace Molitor\Stock\Tests\Feature\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Molitor\Product\Models\Product;
use Molitor\Product\Models\ProductUnit;
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\InventoryRepositoryInterface;
use Tests\TestCase;

class InventoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_inventory_with_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

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

        $repository = $this->app->make(InventoryRepositoryInterface::class);

        $inventory = $repository->create($warehouseRegion, 'Éves leltár');

        $this->assertSame($user->id, $inventory->user_id);
        $this->assertSame($warehouseRegion->id, $inventory->warehouse_region_id);
        $this->assertSame('Éves leltár', $inventory->description);
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'user_id' => $user->id,
            'warehouse_region_id' => $warehouseRegion->id,
            'description' => 'Éves leltár',
        ]);
    }

    public function test_it_creates_inventory_items_from_region_stocks_with_positive_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $warehouse = Warehouse::query()->create([
            'name' => 'Központi raktár',
            'description' => null,
            'is_primary' => true,
        ]);

        $warehouseRegion = WarehouseRegion::query()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'A régió',
            'description' => null,
            'is_primary' => true,
        ]);

        $productUnit = ProductUnit::query()->create(['code' => 'db', 'enabled' => true]);

        $productWithStock = Product::query()->create([
            'active' => true,
            'sku' => 'SKU-001',
            'slug' => 'sku-001',
            'price' => 100,
            'product_unit_id' => $productUnit->id,
        ]);

        $productWithZeroStock = Product::query()->create([
            'active' => true,
            'sku' => 'SKU-002',
            'slug' => 'sku-002',
            'price' => 50,
            'product_unit_id' => $productUnit->id,
        ]);

        Stock::query()->create([
            'warehouse_region_id' => $warehouseRegion->id,
            'product_id' => $productWithStock->id,
            'quantity' => 10,
        ]);

        Stock::query()->create([
            'warehouse_region_id' => $warehouseRegion->id,
            'product_id' => $productWithZeroStock->id,
            'quantity' => 0,
        ]);

        $repository = $this->app->make(InventoryRepositoryInterface::class);
        $inventory = $repository->create($warehouseRegion, 'Tesztelési leltár');

        $this->assertCount(1, $inventory->inventoryItems);

        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $inventory->id,
            'product_id' => $productWithStock->id,
            'old_quantity' => null,
            'new_quantity' => 10,
        ]);

        $this->assertDatabaseMissing('inventory_items', [
            'inventory_id' => $inventory->id,
            'product_id' => $productWithZeroStock->id,
        ]);
    }
}
