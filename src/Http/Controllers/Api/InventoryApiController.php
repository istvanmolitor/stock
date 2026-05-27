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
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\StockRepositoryInterface;

class InventoryApiController extends Controller
{
    use HasAdminFilters;

    public function __construct(
        protected StockRepositoryInterface $stockRepository,
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
            $inventory = Inventory::query()->create([
                'warehouse_region_id' => (int) $validated['warehouse_region_id'],
                'description' => $validated['description'] ?? null,
            ]);

            $stockRows = Stock::query()
                ->where('warehouse_region_id', (int) $validated['warehouse_region_id'])
                ->orderBy('product_id')
                ->get(['product_id', 'quantity']);

            foreach ($stockRows as $stockRow) {
                InventoryItem::query()->create([
                    'inventory_id' => $inventory->id,
                    'product_id' => (int) $stockRow->product_id,
                    'old_quantity' => (float) $stockRow->quantity,
                    'new_quantity' => (float) $stockRow->quantity,
                ]);
            }

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

            foreach ($validated['items'] as $item) {
                $itemId = isset($item['id']) ? (int) $item['id'] : null;

                if ($itemId !== null && $existingItemsById->has($itemId)) {
                    $existingItemsById->get($itemId)?->update([
                        'new_quantity' => (int) $item['new_quantity'],
                    ]);

                    continue;
                }

                InventoryItem::query()->create([
                    'inventory_id' => $inventory->id,
                    'product_id' => (int) $item['product_id'],
                    'old_quantity' => 0,
                    'new_quantity' => (int) $item['new_quantity'],
                ]);
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
}




