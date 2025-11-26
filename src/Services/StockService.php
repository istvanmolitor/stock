<?php

namespace Molitor\Stock\Services;

use Molitor\Stock\Enums\StockMovementType;
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Models\StockMovementItem;

class StockService
{
    public function __construct(
        protected StockReposi
    )
    {

    }

    public function execute(StockMovement $stockMovement): void
    {
        if ($stockMovement->type === StockMovementType::In) {
            $this->incrise($stockMovement);
        }
    }

    public function incrise(StockMovement $stockMovement): void
    {
        $stockMovement->load('stockMovementItems');

        /** @var StockMovementItem $item */
        foreach ($stockMovement->stockMovementItems as $item) {
            if (empty($item->warehouse_region_id) || empty($item->product_id)) {
                continue;
            }

            $stock = Stock::where('warehouse_region_id', $item->warehouse_region_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->quantity = (float) $stock->quantity + (float) $item->quantity;
                $stock->save();
            } else {
                Stock::create([
                    'warehouse_region_id' => $item->warehouse_region_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }
        }
    }
}
