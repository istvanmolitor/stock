<?php

declare(strict_types=1);

namespace Molitor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'is_primary',
        'name',
        'description',
    ];

    public function __toString(): string
    {
        return $this->name;
    }

    public function regions(): HasMany
    {
        return $this->hasMany(WarehouseRegion::class, 'warehouse_id');
    }
}
