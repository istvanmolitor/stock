<?php

namespace Molitor\Stock\database\seeders;

use Illuminate\Database\Seeder;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\User\Exceptions\PermissionException;
use Molitor\User\Repositories\AclRepositoryInterface;
use Molitor\User\Services\AclManagementService;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            /** @var AclManagementService $aclService */
            $aclService = app(AclManagementService::class);
            $aclService->createPermission('stock', 'Készlet', 'admin');
            $aclService->createPermission('stock_movement', 'Készletmozgás', 'admin');
        } catch (PermissionException $e) {
            $this->command->error($e->getMessage());
        }

        if(app()->isLocal()) {
            $data = include __DIR__ . '/data/regions.php';

            foreach($data as $warehouseData) {
                $warehouse = new Warehouse();
                $warehouse->name = $warehouseData['name'];
                $warehouse->description = $warehouseData['description'];
                $warehouse->save();

                foreach($warehouseData['regions'] as $regionData) {
                    $region = new WarehouseRegion();
                    $region->warehouse_id = $warehouse->id;
                    $region->name = $regionData['name'];
                    $region->save();
                }
            }
        }
    }
}
