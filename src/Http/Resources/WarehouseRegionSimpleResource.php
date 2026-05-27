<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseRegionSimpleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $warehouseName = $this->warehouse?->name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $warehouseName,
            'label' => trim(sprintf('%s / %s', (string) ($warehouseName ?? '-'), (string) $this->name)),
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}
