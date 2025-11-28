<?php

namespace Molitor\Stock\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Molitor\Stock\Models\Warehouse;

interface WarehouseRepositoryInterface
{
    public function setPrimary(Warehouse $warehouse): void;

    public function delete(Warehouse $warehouse): bool;

    public function getAll(): Collection;

    public function getDefaultWarehouse(): Warehouse;

    public function getOptions(): array;

    public function getByName(string $name): Warehouse|null;

    public function getDefault(): Warehouse;
}
