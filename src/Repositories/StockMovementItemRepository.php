<?php

namespace Molitor\Stock\Repositories;

use Molitor\Stock\Models\StockMovementItem;

class StockMovementItemRepository implements StockMovementItemRepositoryInterface
{
    protected StockMovementItem $stockMovementItem;

    public function __construct()
    {
        $this->stockMovementItem = new StockMovementItem();
    }
}
