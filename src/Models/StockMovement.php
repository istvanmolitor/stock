<?php

namespace Molitor\Stock\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Molitor\Stock\Enums\StockMovementType;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'type',
        'warehouse_id',
        'linked_stock_movement_id',
        'description',
        'closed_at',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (StockMovement $stockMovement) {
            $stockMovement->user_id = auth()->id();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function linkedStockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'linked_stock_movement_id');
    }

    public function stockMovementItems(): HasMany
    {
        return $this->hasMany(StockMovementItem::class, 'stock_movement_id');
    }
}
