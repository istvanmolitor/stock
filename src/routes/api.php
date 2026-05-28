<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Molitor\Stock\Http\Controllers\Api\InventoryApiController;
use Molitor\Stock\Http\Controllers\Api\StockMovementApiController;
use Molitor\Stock\Http\Controllers\Api\StockProductApiController;
use Molitor\Stock\Http\Controllers\Api\WarehouseApiController;
use Molitor\Stock\Http\Controllers\Api\WarehouseRegionApiController;

Route::prefix('admin/stock')
    ->middleware(['api', 'auth:sanctum'])
    ->name('stock.')
    ->group(function (): void {
        Route::get('products', [StockProductApiController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [StockProductApiController::class, 'show'])->name('products.show');
        Route::put('products/{product}/regions/{warehouseRegion}', [StockProductApiController::class, 'updateRegionQuantityLimits'])
            ->name('products.regions.update-limits');
        Route::delete('products/{product}/regions/{warehouseRegion}/limits', [StockProductApiController::class, 'clearRegionQuantityLimits'])
            ->name('products.regions.clear-limits');
        Route::resource('warehouses', WarehouseApiController::class);
        Route::resource('warehouse-regions', WarehouseRegionApiController::class);
        Route::get('inventories', [InventoryApiController::class, 'index'])->name('inventories.index');
        Route::get('inventories/create', [InventoryApiController::class, 'create'])->name('inventories.create');
        Route::post('inventories', [InventoryApiController::class, 'store'])->name('inventories.store');
        Route::get('inventories/{inventory}/edit', [InventoryApiController::class, 'edit'])->name('inventories.edit');
        Route::put('inventories/{inventory}', [InventoryApiController::class, 'update'])->name('inventories.update');
        Route::delete('inventories/{inventory}', [InventoryApiController::class, 'destroy'])->name('inventories.destroy');
        Route::post('inventories/{inventory}/close', [InventoryApiController::class, 'close'])->name('inventories.close');
        Route::post('movements/{stockMovement}/execute', [StockMovementApiController::class, 'execute'])->name('stock.movements.execute');
        Route::resource('movements', StockMovementApiController::class)->parameters(['movements' => 'stockMovement']);
    });
