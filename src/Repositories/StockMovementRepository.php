<?php

namespace Molitor\Stock\Repositories;

use Molitor\Stock\Models\StockMovement;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    protected StockMovement $stockMovement;

    public function __construct()
    {
        $this->stockMovement = new StockMovement();
    }
}
