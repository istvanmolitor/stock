<?php

declare(strict_types=1);

namespace Molitor\Stock\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Molitor\Stock\Models\Warehouse;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    private Warehouse $warehouse;

    public function __construct()
    {
        $this->warehouse = new Warehouse();
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }

    public function getAll(): Collection
    {
        return $this->warehouse->orderBy('name')->get();
    }

    public function getDefaultWarehouse(): Warehouse
    {
        return $this->warehouse->first();
    }

    public function getOptions(): array
    {
        return $this->warehouse->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getDefaultWarehouseName(): string
    {
        return 'Raktár';
    }

    public function getByName(string $name): Warehouse|null
    {
        return $this->warehouse->where('name', $name)->first();
    }

    public function getDefault(): Warehouse
    {
        $name = $this->getDefaultWarehouseName();
        $defaultWarehouse = $this->getByName($name);
        if($defaultWarehouse) {
            return $defaultWarehouse;
        }
        return $this->warehouse->create([
            'name' => $name,
        ]);
    }
}
