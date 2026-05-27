<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'warehouse_region_id' => $this->warehouse_region_id,
            'description' => $this->description,
            'stock_updated_at' => $this->stock_updated_at?->toDateTimeString(),
            'is_closed' => $this->stock_updated_at !== null,
            'items_count' => $this->whenCounted('inventoryItems'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'warehouse_region' => $this->whenLoaded('warehouseRegion', fn (): array => [
                'id' => $this->warehouseRegion->id,
                'name' => $this->warehouseRegion->name,
                'warehouse_name' => $this->warehouseRegion->warehouse?->name,
                'label' => (string) $this->warehouseRegion,
            ]),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'items' => InventoryItemResource::collection($this->whenLoaded('inventoryItems')),
        ];
    }
}

