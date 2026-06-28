<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Molitor\Stock\DataTables\WarehouseRegionDataTable;
use Molitor\Stock\Http\Requests\StoreWarehouseRegionRequest;
use Molitor\Stock\Http\Requests\UpdateWarehouseRegionRequest;
use Molitor\Stock\Http\Resources\WarehouseRegionResource;
use Molitor\Stock\Http\Resources\WarehouseSimpleResource;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;

class WarehouseRegionApiController extends Controller
{
    public function __construct(
        private WarehouseRegionRepositoryInterface $warehouseRegionRepository
    ) {}

    public function index(WarehouseRegionDataTable $dataTable): AnonymousResourceCollection
    {
        return $dataTable->getResponse();
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'warehouses' => WarehouseSimpleResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreWarehouseRegionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $warehouseRegion = $this->warehouseRegionRepository->create(
            (int) $validated['warehouse_id'],
            $validated['name'],
            $validated['description'] ?? null,
            $validated['is_primary'] ?? false,
        );

        $warehouseRegion->load('warehouse');

        return response()->json([
            'data' => new WarehouseRegionResource($warehouseRegion),
            'message' => 'A telephely régió sikeresen létrejött.',
        ], 201);
    }

    public function show(WarehouseRegion $warehouseRegion): JsonResponse
    {
        $warehouseRegion->load('warehouse');

        return response()->json([
            'data' => new WarehouseRegionResource($warehouseRegion),
            'warehouses' => WarehouseSimpleResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }

    public function edit(WarehouseRegion $warehouseRegion): JsonResponse
    {
        $warehouseRegion->load('warehouse');

        return response()->json([
            'data' => new WarehouseRegionResource($warehouseRegion),
            'warehouses' => WarehouseSimpleResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }

    public function update(UpdateWarehouseRegionRequest $request, WarehouseRegion $warehouseRegion): JsonResponse
    {
        $validated = $request->validated();

        $warehouseRegion->update([
            'warehouse_id' => $validated['warehouse_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        if (($validated['is_primary'] ?? false) === true) {
            $this->setPrimaryRegion($warehouseRegion);
        }

        $warehouseRegion->load('warehouse');

        return response()->json([
            'data' => new WarehouseRegionResource($warehouseRegion),
            'message' => 'A telephely régió sikeresen frissült.',
        ]);
    }

    public function destroy(WarehouseRegion $warehouseRegion): JsonResponse
    {
        $warehouseRegion->delete();

        return response()->json([
            'message' => 'A telephely régió sikeresen törölve lett.',
        ]);
    }

    private function setPrimaryRegion(WarehouseRegion $warehouseRegion): void
    {
        WarehouseRegion::query()
            ->where('warehouse_id', $warehouseRegion->warehouse_id)
            ->whereKeyNot($warehouseRegion->getKey())
            ->update(['is_primary' => false]);

        $warehouseRegion->forceFill(['is_primary' => true])->save();
    }
}
