<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Molitor\Admin\Traits\HasAdminFilters;
use Molitor\Stock\Http\Requests\StoreWarehouseRegionRequest;
use Molitor\Stock\Http\Requests\UpdateWarehouseRegionRequest;
use Molitor\Stock\Http\Resources\WarehouseRegionResource;
use Molitor\Stock\Http\Resources\WarehouseSimpleResource;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

class WarehouseRegionApiController extends Controller
{
    use HasAdminFilters;

    public function index(Request $request): JsonResponse
    {
        $query = WarehouseRegion::query()->with('warehouse');
        $warehouseRegions = $this->applyAdminFilters($query, $request, ['name', 'description'])
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'data' => WarehouseRegionResource::collection($warehouseRegions->items()),
            'meta' => [
                'current_page' => $warehouseRegions->currentPage(),
                'last_page' => $warehouseRegions->lastPage(),
                'per_page' => $warehouseRegions->perPage(),
                'total' => $warehouseRegions->total(),
            ],
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
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

        $warehouseRegion = WarehouseRegion::query()->create([
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

