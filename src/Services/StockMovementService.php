<?php

namespace Molitor\Stock\Services;

use Molitor\Stock\Enums\StockMovementType;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Models\StockMovementItem;
use Molitor\Stock\Repositories\StockRepositoryInterface;

class StockMovementService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository,
    )
    {
    }

    public function execute(StockMovement $stockMovement): array
    {
        if ($stockMovement->type === StockMovementType::In) {
            $this->increase($stockMovement);
            return [];
        }
        elseif ($stockMovement->type === StockMovementType::Out) {
            return $this->decrease($stockMovement);
        }
        elseif ($stockMovement->type === StockMovementType::Transfer) {
            return $this->transfer($stockMovement);
        }
    }

    private function increase(StockMovement $stockMovement): void
    {
        /** @var StockMovementItem $item */
        foreach ($stockMovement->stockMovementItems as $stockMovementItem) {
            $product = $stockMovementItem->product;
            $warehouseRegion = $stockMovementItem->warehouseRegion;
            $currentQuantity = $this->stockRepository->getQuantity($warehouseRegion, $product);
            $this->stockRepository->setQuantity(
                $warehouseRegion,
                $product,
                $currentQuantity + $stockMovementItem->quantity
            );
        }
    }

    private function validateDecrease(StockMovement $stockMovement): array
    {
        foreach ($stockMovement->stockMovementItems as $stockMovementItem) {
            $product = $stockMovementItem->product;
            $warehouseRegion = $stockMovementItem->warehouseRegion;

            $currentQuantity = $this->stockRepository->getQuantity($warehouseRegion, $product);
            $newQuantity = $currentQuantity - $stockMovementItem->quantity;

            if($newQuantity < 0) {
                $errors[] = [
                    'product' => $product,
                    'region' => $warehouseRegion,
                    'quantity' => $stockMovementItem->quantity,
                    'currentQuantity' => $currentQuantity,
                ];
            }
        }

        return $errors;
    }

    private function decrease(StockMovement $stockMovement): array
    {
        $errors = $this->validateDecrease($stockMovement);
        if(count($errors)) {
            return $errors;
        }

        /** @var StockMovementItem $item */
        foreach ($stockMovement->stockMovementItems as $stockMovementItem) {
            $product = $stockMovementItem->product;
            $warehouseRegion = $stockMovementItem->warehouseRegion;

            $currentQuantity = $this->stockRepository->getQuantity($warehouseRegion, $product);
            $this->stockRepository->setQuantity(
                $warehouseRegion,
                $product,
                $currentQuantity - $stockMovementItem->quantity
            );
        }
        return [];
    }

    private function transfer(StockMovement $stockMovement): array
    {
        $errors = $this->decrease($stockMovement);
        if(count($errors)) {
            return $errors;
        }

        $newStockMovement = new StockMovement([
            'type' => StockMovementType::In,
            'warehouse_id' => $stockMovement->warehouse_id,
            'description' => $stockMovement->description,
            'linked_stock_movement_id' => $stockMovement->id,
        ]);
        $newStockMovement->save();

        /** @var StockMovementItem $stockMovementItem */
        foreach ($newStockMovement->stockMovementItems as $stockMovementItem) {
            $newStockMovementItem = new StockMovementItem([
                'stock_movement_id' => $newStockMovement->id,
                'product_id' => $stockMovementItem->product_id,
                'quantity' => $stockMovementItem->quantity,
                'warehouse_region_id' => null,
            ]);
            $newStockMovementItem->save();
        }
        return [];
    }
}
