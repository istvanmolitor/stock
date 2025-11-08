<?php

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseRegion extends Model
{
    protected $fillable = [
        'warehouse_id',
        'name',
        'description',
    ];

    public function __toString()
    {
        return $this->warehouse . '/' . $this->name;
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'warehouse_region_id');
    }
}
