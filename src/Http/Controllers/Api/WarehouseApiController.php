<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Molitor\Stock\DataTables\WarehouseDataTable;
use Molitor\Stock\Http\Requests\StoreWarehouseRequest;
use Molitor\Stock\Http\Requests\UpdateWarehouseRequest;
use Molitor\Stock\Http\Resources\WarehouseResource;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Repositories\WarehouseRepositoryInterface;

class WarehouseApiController extends Controller
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {}

    public function index(WarehouseDataTable $dataTable): AnonymousResourceCollection
    {
        return $dataTable->getResponse();
    }

    public function create(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $warehouse = $this->warehouseRepository->create(
            $validated['name'],
            $validated['description'] ?? null,
            $validated['is_primary'] ?? false,
        );

        $warehouse->load('regions');

        return response()->json([
            'data' => new WarehouseResource($warehouse),
            'message' => 'A raktár sikeresen létrejött.',
        ], 201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load('regions');

        return response()->json([
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function edit(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load('regions');

        return response()->json([
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validated();

        $warehouse->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        if (($validated['is_primary'] ?? false) === true) {
            $this->setPrimaryWarehouse($warehouse);
        }

        $warehouse->load('regions');

        return response()->json([
            'data' => new WarehouseResource($warehouse),
            'message' => 'A raktár sikeresen frissült.',
        ]);
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'A raktár sikeresen törölve lett.',
        ]);
    }

    private function setPrimaryWarehouse(Warehouse $warehouse): void
    {
        Warehouse::query()
            ->whereKeyNot($warehouse->getKey())
            ->update(['is_primary' => false]);

        $warehouse->forceFill(['is_primary' => true])->save();
    }
}
