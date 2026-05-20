<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'warehouse_id' => $this->warehouse_id,
            'description' => $this->description,
            'closed_at' => $this->closed_at?->toDateTimeString(),
            'is_closed' => $this->closed_at !== null,
            'user_id' => $this->user_id,
            'linked_stock_movement_id' => $this->linked_stock_movement_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'items' => StockMovementItemResource::collection($this->whenLoaded('stockMovementItems')),
        ];
    }
}

