<?php

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function __toString()
    {
        return $this->name;
    }

    public function regions()
    {
        return $this->hasMany(WarehouseRegion::class, 'warehouse_id');
    }
}
