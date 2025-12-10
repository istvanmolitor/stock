<?php

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Molitor\Product\Models\Product;

class WarehouseRegionProduct extends Model
{
    protected $table = 'warehouse_region_products';

    public $timestamps = false;

    protected $fillable = [
        'warehouse_region_id',
        'product_id',
        'min_quantity',
        'max_quantity',
    ];

    public function warehouseRegion(): BelongsTo
    {
        return $this->belongsTo(WarehouseRegion::class, 'warehouse_region_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
