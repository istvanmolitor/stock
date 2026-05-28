<?php

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Molitor\Product\Models\Product;

class Stock extends Model
{
    protected $table = 'stocks';

    public $timestamps = false;

    protected $fillable = [
        'warehouse_region_id',
        'product_id',
        'quantity',
        'min_quantity',
        'max_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'max_quantity' => 'decimal:2',
        ];
    }

    public function warehouseRegion(): BelongsTo
    {
        return $this->belongsTo(WarehouseRegion::class, 'warehouse_region_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
