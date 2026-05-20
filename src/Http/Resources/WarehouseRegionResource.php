<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseRegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_primary' => (bool) $this->is_primary,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'warehouse' => new WarehouseSimpleResource($this->whenLoaded('warehouse')),
        ];
    }
}

