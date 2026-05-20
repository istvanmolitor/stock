<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => (float) $this->quantity,
            'warehouse_region_id' => $this->warehouse_region_id,
            'destination_warehouse_region_id' => $this->destination_warehouse_region_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
            ]),
            'warehouse_region' => $this->whenLoaded('warehouseRegion', fn () => [
                'id' => $this->warehouseRegion->id,
                'name' => $this->warehouseRegion->name,
                'warehouse_name' => $this->warehouseRegion->warehouse?->name,
                'label' => sprintf('%s / %s', $this->warehouseRegion->warehouse?->name ?? '-', $this->warehouseRegion->name),
            ]),
            'destination_warehouse_region' => $this->whenLoaded('destinationWarehouseRegion', fn () => $this->destinationWarehouseRegion ? [
                'id' => $this->destinationWarehouseRegion->id,
                'name' => $this->destinationWarehouseRegion->name,
                'warehouse_name' => $this->destinationWarehouseRegion->warehouse?->name,
                'label' => sprintf('%s / %s', $this->destinationWarehouseRegion->warehouse?->name ?? '-', $this->destinationWarehouseRegion->name),
            ] : null),
        ];
    }
}

