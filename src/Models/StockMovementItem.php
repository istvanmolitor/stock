<?php

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Molitor\Product\Models\Product;

class StockMovementItem extends Model
{
    protected $table = 'stock_movement_items';

    public $timestamps = true;

    protected $fillable = [
        'stock_movement_id',
        'product_id',
        'quantity',
        'warehouse_region_id',
    ];

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouseRegion(): BelongsTo
    {
        return $this->belongsTo(WarehouseRegion::class, 'warehouse_region_id');
    }
}
