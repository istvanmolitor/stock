<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockProductRegionQuantityLimitsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'warehouse_region_id' => $this->warehouse_region_id,
            'warehouse_region_name' => $this->warehouseRegion?->name,
            'warehouse_name' => $this->warehouseRegion?->warehouse?->name,
            'quantity' => (float) $this->quantity,
            'min_quantity' => $this->min_quantity !== null ? (float) $this->min_quantity : null,
            'max_quantity' => $this->max_quantity !== null ? (float) $this->max_quantity : null,
        ];
    }
}

