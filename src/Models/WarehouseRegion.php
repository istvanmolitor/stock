<?php

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Molitor\Product\Models\Product;

class WarehouseRegion extends Model
{
    protected $fillable = [
        'warehouse_id',
        'is_primary',
        'name',
        'description',
    ];

    public function __toString()
    {
        return $this->warehouse . '/' . $this->name;
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'warehouse_region_id');
    }

    public function warehouseRegionProducts(): HasMany
    {
        return $this->hasMany(WarehouseRegionProduct::class, 'warehouse_region_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'warehouse_region_products', 'warehouse_region_id', 'product_id')
            ->withPivot(['min_quantity', 'max_quantity']);
    }
}
