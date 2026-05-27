<?php

declare(strict_types=1);

namespace Molitor\Stock\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $fillable = [
        'user_id',
        'warehouse_region_id',
        'description',
        'stock_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Inventory $inventory): void {
            if ($inventory->user_id === null) {
                $inventory->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouseRegion(): BelongsTo
    {
        return $this->belongsTo(WarehouseRegion::class, 'warehouse_region_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_id');
    }
}

