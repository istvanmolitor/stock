<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Molitor\Stock\Models\Warehouse;
use Tests\TestCase;

class StockApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('purchase_logs');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('stock_movement_items');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('warehouse_region_products');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('warehouse_regions');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('products');
        Schema::enableForeignKeyConstraints();

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_primary')->default(false);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('name');
            $table->boolean('is_primary')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku');
            $table->string('slug')->nullable();
            $table->decimal('price')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('product_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stocks', function (Blueprint $table): void {
            $table->foreignId('warehouse_region_id')->constrained('warehouse_regions');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity');
            $table->decimal('min_quantity')->nullable();
            $table->decimal('max_quantity')->nullable();
            $table->primary(['warehouse_region_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->unsignedBigInteger('linked_stock_movement_id')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained('stock_movements');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_region_id')->constrained('warehouse_regions');
            $table->unsignedBigInteger('destination_warehouse_region_id')->nullable();
            $table->foreign('destination_warehouse_region_id')->references('id')->on('warehouse_regions');
            $table->decimal('quantity');
            $table->timestamps();
        });

        Schema::create('inventories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('warehouse_region_id')->constrained('warehouse_regions')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamp('stock_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('old_quantity', 16, 4)->nullable()->default(null);
            $table->decimal('new_quantity', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(['inventory_id', 'product_id']);
        });
    }

    public function test_can_manage_warehouses(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $createResponse = $this->postJson('/api/admin/stock/warehouses', [
            'name' => 'Központi raktár',
            'description' => 'Fő telephely',
            'is_primary' => true,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Központi raktár');

        $warehouseId = $createResponse->json('data.id');

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouseId,
            'name' => 'Központi raktár',
        ]);

        $updateResponse = $this->putJson("/api/admin/stock/warehouses/{$warehouseId}", [
            'name' => 'Frissített raktár',
            'description' => 'Frissített leírás',
            'is_primary' => false,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'Frissített raktár');

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouseId,
            'name' => 'Frissített raktár',
        ]);

        $deleteResponse = $this->deleteJson("/api/admin/stock/warehouses/{$warehouseId}");

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouseId]);
    }

    public function test_can_manage_warehouse_regions(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $warehouse = Warehouse::query()->create([
            'name' => 'Raktár 1',
            'description' => 'Teszt raktár',
            'is_primary' => false,
        ]);

        $createResponse = $this->postJson('/api/admin/stock/warehouse-regions', [
            'warehouse_id' => $warehouse->id,
            'name' => 'Első régió',
            'description' => 'Teszt régió',
            'is_primary' => true,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Első régió');

        $regionId = $createResponse->json('data.id');

        $this->assertDatabaseHas('warehouse_regions', [
            'id' => $regionId,
            'name' => 'Első régió',
            'warehouse_id' => $warehouse->id,
        ]);

        $updateResponse = $this->putJson("/api/admin/stock/warehouse-regions/{$regionId}", [
            'warehouse_id' => $warehouse->id,
            'name' => 'Frissített régió',
            'description' => 'Frissített leírás',
            'is_primary' => false,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'Frissített régió');

        $this->assertDatabaseHas('warehouse_regions', [
            'id' => $regionId,
            'name' => 'Frissített régió',
        ]);

        $deleteResponse = $this->deleteJson("/api/admin/stock/warehouse-regions/{$regionId}");

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('warehouse_regions', ['id' => $regionId]);
    }

    public function test_can_create_stock_movement_draft_and_execute_in(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Raktár', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id, 'name' => 'A régió', 'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-001', 'slug' => 'sku-001', 'price' => 0, 'active' => true,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
        ]);

        $createResponse = $this->postJson('/api/admin/stock/movements', [
            'type' => 'in',
            'description' => 'Bevételezés',
            'items' => [['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 10]],
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('message', 'A készletmozgás sikeresen rögzítve lett.')
            ->assertJsonPath('data.is_closed', false);

        $movementId = $createResponse->json('data.id');
        $this->assertDatabaseMissing('stocks', ['warehouse_region_id' => $regionId, 'product_id' => $productId]);

        $executeResponse = $this->postJson("/api/admin/stock/movements/{$movementId}/execute");
        $executeResponse->assertOk()
            ->assertJsonPath('message', 'A készletmozgás sikeresen végrehajtva lett.')
            ->assertJsonPath('data.is_closed', true);

        $this->assertDatabaseHas('stocks', ['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 10]);
    }

    public function test_can_update_draft_before_execute(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Raktár', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id, 'name' => 'A régió', 'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-002', 'slug' => 'sku-002', 'price' => 0, 'active' => true,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
        ]);

        $createResponse = $this->postJson('/api/admin/stock/movements', [
            'type' => 'in',
            'items' => [['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 5]],
        ]);
        $movementId = $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/admin/stock/movements/{$movementId}", [
            'type' => 'in',
            'description' => 'Módosított leírás',
            'items' => [['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 20]],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('message', 'A készletmozgás sikeresen frissítve lett.');

        $this->assertDatabaseHas('stock_movement_items', ['stock_movement_id' => $movementId, 'quantity' => 20]);
    }

    public function test_cannot_execute_out_when_not_enough_stock(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Raktár', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id, 'name' => 'A régió', 'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-003', 'slug' => 'sku-003', 'price' => 0, 'active' => true,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
        ]);
        DB::table('stocks')->insert(['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 2]);

        $createResponse = $this->postJson('/api/admin/stock/movements', [
            'type' => 'out',
            'items' => [['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 3]],
        ]);
        $movementId = $createResponse->json('data.id');

        $this->postJson("/api/admin/stock/movements/{$movementId}/execute")->assertUnprocessable();

        $this->assertDatabaseHas('stocks', ['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 2]);
    }

    public function test_can_transfer_stock_between_regions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Raktár', 'is_primary' => true]);
        $sourceId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id, 'name' => 'Forrás', 'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $destId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id, 'name' => 'Cél', 'is_primary' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-004', 'slug' => 'sku-004', 'price' => 0, 'active' => true,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
        ]);
        DB::table('stocks')->insert(['warehouse_region_id' => $sourceId, 'product_id' => $productId, 'quantity' => 12]);

        $createResponse = $this->postJson('/api/admin/stock/movements', [
            'type' => 'transfer',
            'items' => [[
                'warehouse_region_id' => $sourceId,
                'destination_warehouse_region_id' => $destId,
                'product_id' => $productId,
                'quantity' => 5,
            ]],
        ]);
        $movementId = $createResponse->json('data.id');

        $this->postJson("/api/admin/stock/movements/{$movementId}/execute")
            ->assertOk()
            ->assertJsonPath('message', 'A készletmozgás sikeresen végrehajtva lett.');

        $this->assertDatabaseHas('stocks', ['warehouse_region_id' => $sourceId, 'product_id' => $productId, 'quantity' => 7]);
        $this->assertDatabaseHas('stocks', ['warehouse_region_id' => $destId, 'product_id' => $productId, 'quantity' => 5]);
    }

    public function test_can_delete_draft_movement(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Raktár', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id, 'name' => 'A régió', 'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-005', 'slug' => 'sku-005', 'price' => 0, 'active' => true,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null,
        ]);

        $createResponse = $this->postJson('/api/admin/stock/movements', [
            'type' => 'in',
            'items' => [['warehouse_region_id' => $regionId, 'product_id' => $productId, 'quantity' => 5]],
        ]);
        $movementId = $createResponse->json('data.id');

        $this->deleteJson("/api/admin/stock/movements/{$movementId}")->assertOk();
        $this->assertDatabaseMissing('stock_movements', ['id' => $movementId]);
        $this->assertDatabaseMissing('stock_movement_items', ['stock_movement_id' => $movementId]);
    }

    public function test_can_list_products_with_region_quantities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Központ', 'is_primary' => true]);
        $regionAId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'A régió',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $regionBId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'B régió',
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-STOCK-1',
            'slug' => 'sku-stock-1',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            ['warehouse_region_id' => $regionAId, 'product_id' => $productId, 'quantity' => 4],
            ['warehouse_region_id' => $regionBId, 'product_id' => $productId, 'quantity' => 6],
        ]);

        $response = $this->getJson('/api/admin/stock/products');

        $response->assertOk()
            ->assertJsonPath('data.0.sku', 'SKU-STOCK-1')
            ->assertJsonPath('data.0.total_quantity', 10)
            ->assertJsonPath('data.0.region_quantities.0.quantity', 4)
            ->assertJsonPath('data.0.region_quantities.1.quantity', 6);
    }

    public function test_can_show_product_stock_detail_grouped_by_warehouse_and_region(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouseA = Warehouse::query()->create(['name' => 'Központ', 'is_primary' => true]);
        $warehouseB = Warehouse::query()->create(['name' => 'Outlet', 'is_primary' => false]);

        $regionA1Id = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouseA->id,
            'name' => 'A1',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $regionA2Id = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouseA->id,
            'name' => 'A2',
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $regionB1Id = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouseB->id,
            'name' => 'B1',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-STOCK-DETAIL',
            'slug' => 'sku-stock-detail',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            ['warehouse_region_id' => $regionA1Id, 'product_id' => $productId, 'quantity' => 3, 'min_quantity' => 1, 'max_quantity' => 10],
            ['warehouse_region_id' => $regionA2Id, 'product_id' => $productId, 'quantity' => 2, 'min_quantity' => null, 'max_quantity' => null],
            ['warehouse_region_id' => $regionB1Id, 'product_id' => $productId, 'quantity' => 5, 'min_quantity' => 2, 'max_quantity' => 12],
        ]);

        $response = $this->getJson("/api/admin/stock/products/{$productId}");

        $response->assertOk()
            ->assertJsonPath('data.sku', 'SKU-STOCK-DETAIL')
            ->assertJsonPath('data.total_quantity', 10)
            ->assertJsonPath('data.warehouse_count', 2)
            ->assertJsonPath('data.region_count', 3)
            ->assertJsonPath('data.warehouses.0.warehouse_name', 'Központ')
            ->assertJsonPath('data.warehouses.0.total_quantity', 5)
            ->assertJsonPath('data.warehouses.0.regions.0.warehouse_region_name', 'A1')
            ->assertJsonPath('data.warehouses.0.regions.0.quantity', 3)
            ->assertJsonPath('data.warehouses.0.regions.0.min_quantity', 1)
            ->assertJsonPath('data.warehouses.0.regions.0.max_quantity', 10)
            ->assertJsonPath('data.warehouses.0.regions.0.has_limits', true)
            ->assertJsonPath('data.warehouses.0.regions.1.warehouse_region_name', 'A2')
            ->assertJsonPath('data.warehouses.0.regions.1.quantity', 2)
            ->assertJsonPath('data.warehouses.0.regions.1.has_limits', false)
            ->assertJsonPath('data.warehouses.1.warehouse_name', 'Outlet')
            ->assertJsonPath('data.warehouses.1.total_quantity', 5)
            ->assertJsonPath('data.warehouses.1.regions.0.warehouse_region_name', 'B1')
            ->assertJsonPath('data.warehouses.1.regions.0.quantity', 5)
            ->assertJsonPath('data.warehouses.1.regions.0.min_quantity', 2)
            ->assertJsonPath('data.warehouses.1.regions.0.max_quantity', 12)
            ->assertJsonPath('data.warehouses.1.regions.0.has_limits', true);
    }

    public function test_can_update_product_region_min_and_max_quantities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Kozpont', 'is_primary' => true]);

        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'A1',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-LIMIT-1',
            'slug' => 'sku-limit-1',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            'warehouse_region_id' => $regionId,
            'product_id' => $productId,
            'quantity' => 7,
            'min_quantity' => null,
            'max_quantity' => null,
        ]);

        $response = $this->putJson("/api/admin/stock/products/{$productId}/regions/{$regionId}", [
            'min_quantity' => 2,
            'max_quantity' => 15,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.warehouse_region_id', $regionId)
            ->assertJsonPath('data.min_quantity', 2)
            ->assertJsonPath('data.max_quantity', 15);

        $this->assertDatabaseHas('stocks', [
            'warehouse_region_id' => $regionId,
            'product_id' => $productId,
            'quantity' => 7,
            'min_quantity' => 2,
            'max_quantity' => 15,
        ]);
    }

    public function test_can_clear_product_region_min_and_max_quantities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Kozpont', 'is_primary' => true]);

        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'A1',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-LIMIT-DELETE-1',
            'slug' => 'sku-limit-delete-1',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            'warehouse_region_id' => $regionId,
            'product_id' => $productId,
            'quantity' => 7,
            'min_quantity' => 2,
            'max_quantity' => 15,
        ]);

        $response = $this->deleteJson("/api/admin/stock/products/{$productId}/regions/{$regionId}/limits");

        $response->assertOk()
            ->assertJsonPath('data.warehouse_region_id', $regionId)
            ->assertJsonPath('data.min_quantity', null)
            ->assertJsonPath('data.max_quantity', null);

        $this->assertDatabaseHas('stocks', [
            'warehouse_region_id' => $regionId,
            'product_id' => $productId,
            'quantity' => 7,
            'min_quantity' => null,
            'max_quantity' => null,
        ]);
    }

    public function test_can_create_update_and_close_inventory_for_region_stock(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Központ', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'Leltár régió',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productAId = DB::table('products')->insertGetId([
            'sku' => 'SKU-INV-A',
            'slug' => 'sku-inv-a',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
        $productBId = DB::table('products')->insertGetId([
            'sku' => 'SKU-INV-B',
            'slug' => 'sku-inv-b',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
        $productCId = DB::table('products')->insertGetId([
            'sku' => 'SKU-INV-C',
            'slug' => 'sku-inv-c',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            ['warehouse_region_id' => $regionId, 'product_id' => $productAId, 'quantity' => 5],
            ['warehouse_region_id' => $regionId, 'product_id' => $productBId, 'quantity' => 3],
        ]);

        $createResponse = $this->postJson('/api/admin/stock/inventories', [
            'warehouse_region_id' => $regionId,
            'description' => 'Nyitó leltár',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.warehouse_region_id', $regionId)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.is_closed', false);

        $inventoryId = $createResponse->json('data.id');
        $items = $createResponse->json('data.items');

        $this->assertCount(2, $items);

        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $inventoryId,
            'product_id' => $productAId,
            'old_quantity' => null,
            'new_quantity' => 5,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $inventoryId,
            'product_id' => $productBId,
            'old_quantity' => null,
            'new_quantity' => 3,
        ]);

        $updateResponse = $this->putJson("/api/admin/stock/inventories/{$inventoryId}", [
            'description' => 'Lezárás előtti módosítás',
            'items' => [
                ['product_id' => $productAId, 'new_quantity' => 7],
                ['product_id' => $productBId, 'new_quantity' => 1],
                ['product_id' => $productCId, 'new_quantity' => 4],
            ],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.description', 'Lezárás előtti módosítás');

        $closeResponse = $this->postJson("/api/admin/stock/inventories/{$inventoryId}/close");

        $closeResponse->assertOk()
            ->assertJsonPath('data.is_closed', true);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventoryId,
            'user_id' => $user->id,
            'warehouse_region_id' => $regionId,
            'description' => 'Lezárás előtti módosítás',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $inventoryId,
            'product_id' => $productAId,
            'old_quantity' => 5,
            'new_quantity' => 7,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $inventoryId,
            'product_id' => $productBId,
            'old_quantity' => 3,
            'new_quantity' => 1,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'inventory_id' => $inventoryId,
            'product_id' => $productCId,
            'old_quantity' => 0,
            'new_quantity' => 4,
        ]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_region_id' => $regionId,
            'product_id' => $productAId,
            'quantity' => 7,
        ]);
        $this->assertDatabaseHas('stocks', [
            'warehouse_region_id' => $regionId,
            'product_id' => $productBId,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('stocks', [
            'warehouse_region_id' => $regionId,
            'product_id' => $productCId,
            'quantity' => 4,
        ]);
    }

    public function test_can_remove_all_items_from_open_inventory(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Szerkesztés teszt raktár', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'Szerkesztés régió',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-INV-REMOVE-ITEM',
            'slug' => 'sku-inv-remove-item',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            'warehouse_region_id' => $regionId,
            'product_id' => $productId,
            'quantity' => 6,
        ]);

        $createResponse = $this->postJson('/api/admin/stock/inventories', [
            'warehouse_region_id' => $regionId,
            'description' => 'Eltávolítható tétel',
        ]);

        $createResponse->assertCreated();

        $inventoryId = (int) $createResponse->json('data.id');
        $items = $createResponse->json('data.items');

        $this->assertCount(1, $items);

        $addItemResponse = $this->putJson("/api/admin/stock/inventories/{$inventoryId}", [
            'description' => 'Tétel hozzáadva',
            'items' => [
                ['product_id' => $productId, 'new_quantity' => 6],
            ],
        ]);

        $addItemResponse->assertOk()
            ->assertJsonPath('data.description', 'Tétel hozzáadva')
            ->assertJsonCount(1, 'data.items');

        $itemId = (int) $addItemResponse->json('data.items.0.id');

        $this->putJson("/api/admin/stock/inventories/{$inventoryId}", [
            'description' => 'Minden tétel törölve',
            'items' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Minden tétel törölve')
            ->assertJsonCount(0, 'data.items');

        $this->assertDatabaseMissing('inventory_items', [
            'id' => $itemId,
            'inventory_id' => $inventoryId,
        ]);
    }

    public function test_can_delete_open_inventory_but_cannot_delete_closed_inventory(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::query()->create(['name' => 'Törlés teszt raktár', 'is_primary' => true]);
        $regionId = DB::table('warehouse_regions')->insertGetId([
            'warehouse_id' => $warehouse->id,
            'name' => 'Törlés régió',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'sku' => 'SKU-INV-DELETE',
            'slug' => 'sku-inv-delete',
            'price' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('stocks')->insert([
            'warehouse_region_id' => $regionId,
            'product_id' => $productId,
            'quantity' => 8,
        ]);

        $createResponse = $this->postJson('/api/admin/stock/inventories', [
            'warehouse_region_id' => $regionId,
            'description' => 'Törölhető leltár',
        ]);

        $createResponse->assertCreated();
        $inventoryId = (int) $createResponse->json('data.id');

        $this->deleteJson("/api/admin/stock/inventories/{$inventoryId}")
            ->assertOk()
            ->assertJsonPath('message', 'A leltár sikeresen törölve lett.');

        $this->assertDatabaseMissing('inventories', ['id' => $inventoryId]);
        $this->assertDatabaseMissing('inventory_items', ['inventory_id' => $inventoryId]);

        $closedInventoryId = DB::table('inventories')->insertGetId([
            'user_id' => $user->id,
            'warehouse_region_id' => $regionId,
            'description' => 'Lezárt leltár',
            'stock_updated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/api/admin/stock/inventories/{$closedInventoryId}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A lezárt leltár nem törölhető.');

        $this->assertDatabaseHas('inventories', ['id' => $closedInventoryId]);
    }
}

