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
        $this->warehouse = new Warehouse;
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

    public function getByName(string $name): ?Warehouse
    {
        return $this->warehouse->where('name', $name)->first();
    }

    public function getDefault(): Warehouse
    {
        $name = $this->getDefaultWarehouseName();
        $defaultWarehouse = $this->getByName($name);
        if ($defaultWarehouse) {
            return $defaultWarehouse;
        }

        return $this->warehouse->create([
            'name' => $name,
        ]);
    }

    public function create(string $name, ?string $description, bool $isPrimary): Warehouse
    {
        $warehouse = $this->warehouse->create([
            'name' => $name,
            'description' => $description,
            'is_primary' => $isPrimary,
        ]);

        if ($isPrimary) {
            $this->setPrimary($warehouse);
        }

        return $warehouse;
    }

    public function setPrimary(Warehouse $warehouse): void
    {
        $this->warehouse->where('id', '<>', $warehouse->id)->update(['is_primary' => false]);
        $warehouse->is_primary = true;
        $warehouse->save();
    }
}
