<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Molitor\Stock\Http\Controllers\Api\WarehouseApiController;
use Molitor\Stock\Http\Controllers\Api\WarehouseRegionApiController;

Route::prefix('admin/stock')
    ->middleware(['api', 'auth:sanctum'])
    ->name('stock.')
    ->group(function (): void {
        Route::resource('warehouses', WarehouseApiController::class);
        Route::resource('warehouse-regions', WarehouseRegionApiController::class);
    });

