<?php

namespace Molitor\Stock\Repositories;

use Molitor\Stock\Models\Stock;

class StockRepository implements StockRepositoryInterface
{
    protected Stock $stock;

    public function __construct()
    {
        $this->stock = new Stock();
    }
}
