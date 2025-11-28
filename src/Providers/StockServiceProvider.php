<?php

namespace Molitor\Stock\Providers;

use Illuminate\Support\ServiceProvider;
use Molitor\Product\Models\Product;
use Molitor\Setting\Services\SettingHandlerService;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\StockMovementItemRepository;
use Molitor\Stock\Repositories\StockMovementItemRepositoryInterface;
use Molitor\Stock\Repositories\StockMovementRepository;
use Molitor\Stock\Repositories\StockMovementRepositoryInterface;
use Molitor\Stock\Repositories\StockRepository;
use Molitor\Stock\Repositories\StockRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRegionRepository;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRepository;
use Molitor\Stock\Repositories\WarehouseRepositoryInterface;
use Molitor\Stock\Services\StockSettingForm;

class StockServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'stock');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'stock');

        if (class_exists(Product::class)) {
            Product::resolveRelationUsing('warehouseRegions', function (Product $product) {
                return $product->belongsToMany(
                    WarehouseRegion::class,
                    'stocks',
                    'product_id',
                    'warehouse_region_id'
                )->withPivot(['quantity']);
            });
        }

        $this->app->make(SettingHandlerService::class)->register(StockSettingForm::class);
    }

    public function register()
    {
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(WarehouseRegionRepositoryInterface::class, WarehouseRegionRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(StockMovementItemRepositoryInterface::class, StockMovementItemRepository::class);
        $this->app->bind(StockRepositoryInterface::class, StockRepository::class);
    }
}
