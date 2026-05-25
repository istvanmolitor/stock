<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
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
        Route::resource('warehouses', WarehouseApiController::class);
        Route::resource('warehouse-regions', WarehouseRegionApiController::class);
        Route::post('movements/{stockMovement}/execute', [StockMovementApiController::class, 'execute'])->name('stock.movements.execute');
        Route::resource('movements', StockMovementApiController::class)->parameters(['movements' => 'stockMovement']);
    });
