<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Molitor\Admin\Traits\HasAdminFilters;
use Molitor\Stock\Http\Requests\StoreInventoryRequest;
use Molitor\Stock\Http\Requests\UpdateInventoryRequest;
use Molitor\Stock\Http\Resources\InventoryResource;
use Molitor\Stock\Http\Resources\WarehouseRegionSimpleResource;
use Molitor\Stock\Models\Inventory;
use Molitor\Stock\Models\InventoryItem;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\InventoryRepositoryInterface;
use Molitor\Stock\Repositories\StockRepositoryInterface;
use Molitor\Stock\Repositories\WarehouseRegionRepositoryInterface;

class InventoryApiController extends Controller
{
    use HasAdminFilters;

    public function __construct(
        protected InventoryRepositoryInterface $inventoryRepository,
        protected StockRepositoryInterface $stockRepository,
        protected WarehouseRegionRepositoryInterface $warehouseRegionRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Inventory::query()
            ->with(['warehouseRegion.warehouse:id,name', 'user:id,name'])
            ->withCount('inventoryItems');

        $inventories = $this->applyAdminFilters($query, $request, ['description'], '')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => InventoryResource::collection($inventories->items()),
            'meta' => [
                'current_page' => $inventories->currentPage(),
                'last_page' => $inventories->lastPage(),
                'per_page' => $inventories->perPage(),
                'total' => $inventories->total(),
            ],
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'warehouse_regions' => WarehouseRegionSimpleResource::collection(
                WarehouseRegion::query()->with('warehouse:id,name')->orderBy('name')->get()
            ),
        ]);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $inventory = DB::transaction(function () use ($validated): Inventory {
            $warehouseRegionId = (int) $validated['warehouse_region_id'];
            $warehouseRegion = $this->warehouseRegionRepository->findOrFail($warehouseRegionId);

            $inventory = $this->inventoryRepository->create(
                $warehouseRegion,
                $validated['description'] ?? null,
            );


            return $inventory;
        });

        $inventory->load([
            'warehouseRegion.warehouse:id,name',
            'user:id,name',
            'inventoryItems.product:id,sku',
        ]);

        return response()->json([
            'data' => new InventoryResource($inventory),
            'message' => 'A leltár sikeresen létrejött.',
        ], 201);
    }

    public function edit(Inventory $inventory): JsonResponse
    {
        $inventory->load([
            'warehouseRegion.warehouse:id,name',
            'user:id,name',
            'inventoryItems.product:id,sku',
        ]);

        return response()->json([
            'data' => new InventoryResource($inventory),
            'warehouse_regions' => WarehouseRegionSimpleResource::collection(
                WarehouseRegion::query()->with('warehouse:id,name')->orderBy('name')->get()
            ),
        ]);
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        if ($inventory->stock_updated_at !== null) {
            return response()->json(['message' => 'A lezárt leltár nem szerkeszthető.'], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $inventory): void {
            $inventory->update([
                'description' => $validated['description'] ?? null,
            ]);

            $items = collect($validated['items']);
            $productIds = $items->pluck('product_id')->map(fn (mixed $productId): int => (int) $productId)->all();

            if (count($productIds) !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => ['Ugyanaz a termék csak egyszer szerepelhet a leltárban.'],
                ]);
            }

            $existingItemsById = $inventory->inventoryItems()
                ->get()
                ->keyBy('id');

            $existingItemsByProductId = $existingItemsById->keyBy('product_id');

            $submittedExistingItemIds = [];

            foreach ($validated['items'] as $item) {
                $itemId = isset($item['id']) ? (int) $item['id'] : null;

                if ($itemId !== null) {
                    if (! $existingItemsById->has($itemId)) {
                        throw ValidationException::withMessages([
                            'items' => ['A megadott tétel nem ehhez a leltárhoz tartozik.'],
                        ]);
                    }

                    $existingItemsById->get($itemId)?->update([
                        'new_quantity' => (int) $item['new_quantity'],
                    ]);

                    $submittedExistingItemIds[] = $itemId;

                    continue;
                }

                $productId = (int) $item['product_id'];
                $existingByProduct = $existingItemsByProductId->get($productId);

                if ($existingByProduct !== null) {
                    $existingByProduct->update([
                        'new_quantity' => (int) $item['new_quantity'],
                    ]);

                    $submittedExistingItemIds[] = $existingByProduct->id;

                    continue;
                }

                InventoryItem::query()->create([
                    'inventory_id' => $inventory->id,
                    'product_id' => $productId,
                    'old_quantity' => null,
                    'new_quantity' => (int) $item['new_quantity'],
                ]);
            }

            $itemIdsToDelete = $existingItemsById
                ->keys()
                ->diff($submittedExistingItemIds);

            if ($itemIdsToDelete->isNotEmpty()) {
                $inventory->inventoryItems()
                    ->whereIn('id', $itemIdsToDelete->all())
                    ->delete();
            }
        });

        $inventory->load([
            'warehouseRegion.warehouse:id,name',
            'user:id,name',
            'inventoryItems.product:id,sku',
        ]);

        return response()->json([
            'data' => new InventoryResource($inventory),
            'message' => 'A leltár tételei sikeresen frissültek.',
        ]);
    }

    public function close(Inventory $inventory): JsonResponse
    {
        if ($inventory->stock_updated_at !== null) {
            return response()->json(['message' => 'A leltár már le van zárva.'], 422);
        }

        $inventory->load(['warehouseRegion', 'inventoryItems.product']);

        DB::transaction(function () use ($inventory): void {
            $region = $inventory->warehouseRegion;

            foreach ($inventory->inventoryItems as $item) {
                $product = $item->product;

                $currentQuantity = $this->stockRepository->getQuantity($region, $product);

                $item->forceFill([
                    'old_quantity' => $currentQuantity,
                ])->save();

                $this->stockRepository->setQuantity($region, $product, (int) $item->new_quantity);
            }

            $inventory->forceFill(['stock_updated_at' => now()])->save();
        });

        $inventory->load([
            'warehouseRegion.warehouse:id,name',
            'user:id,name',
            'inventoryItems.product:id,sku',
        ]);

        return response()->json([
            'data' => new InventoryResource($inventory),
            'message' => 'A leltár sikeresen lezárva, a készlet frissítve.',
        ]);
    }

    public function destroy(Inventory $inventory): JsonResponse
    {
        if ($inventory->stock_updated_at !== null) {
            return response()->json(['message' => 'A lezárt leltár nem törölhető.'], 422);
        }

        $inventory->delete();

        return response()->json([
            'message' => 'A leltár sikeresen törölve lett.',
        ]);
    }
}




