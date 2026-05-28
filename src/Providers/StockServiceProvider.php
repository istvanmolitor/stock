<?php

namespace Molitor\Stock\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Molitor\Product\Models\Product;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\InventoryRepository;
use Molitor\Stock\Repositories\InventoryRepositoryInterface;
use Molitor\Stock\Repositories\StockMovementItemRepository;
use Molitor\Stock\Repositories\StockMovementItemRepositoryInterface;
use Molitor\Stock\Repositories\StockMovementRepository;
use Molitor\Stock\Repositories\StockMovementRepositoryInterface;
use Molitor\Stock\Repositories\StockRepository;
use Molitor\Stock\Repositories\StockRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRegionProductRepository;
use Molitor\Stock\Repositories\WarehouseRegionProductRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRegionRepository;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRepository;
use Molitor\Stock\Repositories\WarehouseRepositoryInterface;

class StockServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'stock');

        $this->app->make(Router::class)
            ->group(['prefix' => 'api'], __DIR__.'/../routes/api.php');

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
    }

    public function register(): void
    {
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(WarehouseRegionRepositoryInterface::class, WarehouseRegionRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(StockMovementItemRepositoryInterface::class, StockMovementItemRepository::class);
        $this->app->bind(StockRepositoryInterface::class, StockRepository::class);
        $this->app->bind(WarehouseRegionProductRepositoryInterface::class, WarehouseRegionProductRepository::class);
    }
}
