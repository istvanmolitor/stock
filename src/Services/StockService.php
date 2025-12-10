<?php

namespace Molitor\Stock\Services;

use Molitor\Product\Models\Product;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\StockRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRegionProductRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRepositoryInterface;

class StockService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository,
        protected WarehouseRepositoryInterface $warehouseRepository,
        protected WarehouseRegionRepositoryInterface $warehouseRegionRepository,
        protected WarehouseRegionProductRepositoryInterface $warehouseRegionProductRepository

    )
    {
    }

    protected function getMinStock(WarehouseRegion|Warehouse|null $location, Product $product): int
    {
        if($location instanceof Warehouse) {
            return $this->warehouseRegionProductRepository->getMinQuantityByWarehouse($location, $product);
        }
        elseif ($location instanceof WarehouseRegion) {
            return $this->warehouseRegionProductRepository->getMinQuantity($location, $product);
        }
        return $this->warehouseRegionProductRepository->getAllMinQuantity($product);
    }

    public function getStock(WarehouseRegion|Warehouse|null $location, Product $product): int
    {
        if($location instanceof Warehouse) {
            return $this->stockRepository->getQuantityByWarehouse($location, $product);
        }
        elseif ($location instanceof WarehouseRegion) {
            return $this->stockRepository->getQuantity($location, $product);
        }
        return $this->stockRepository->getAllQuantity($product);
    }

    public function setStock(WarehouseRegion|Warehouse|null $location, Product $product, int $quantity): void
    {
        if($location === null) {
            $warehouse = $this->warehouseRepository->getDefault();
            $region = $this->warehouseRegionRepository->getDefault($warehouse);
            $this->stockRepository->setQuantity($region, $product, $quantity);
        }
        elseif($location instanceof Warehouse) {
            $region = $this->warehouseRegionRepository->getDefault($location);
            $this->stockRepository->setQuantity($region, $product, $quantity);
        }
        elseif ($location instanceof WarehouseRegion) {
            $this->stockRepository->setQuantity($location, $product, $quantity);
        }
    }

    public function moveStock(WarehouseRegion $source, WarehouseRegion $destionation, Product $product, int $quantity): void
    {
        $currentQuantity = $this->stockRepository->getQuantity($source, $product);
        if($currentQuantity >= $quantity) {
            $this->stockRepository->setQuantity($source, $product, $currentQuantity - $quantity);
            $destinationQuantity = $this->stockRepository->getQuantity($destionation, $product);
            $this->stockRepository->setQuantity($destionation, $product, $destinationQuantity + $quantity);
        }
    }

    public function isFew(WarehouseRegion|Warehouse|null $location, Product $product): bool
    {
        $stock = $this->getStock($location, $product);
        return $stock < $this->getMinStock($location, $product);
    }

    public function isMany(WarehouseRegion|Warehouse|null $location, Product $product): bool
    {
        $stock = $this->getStock($location, $product);
        return $stock > $this->getMinStock($location, $product);
    }
}
