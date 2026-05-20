<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Molitor\Admin\Traits\HasAdminFilters;
use Molitor\Product\Models\Product;
use Molitor\Stock\Enums\StockMovementType;
use Molitor\Stock\Http\Requests\StoreStockMovementDraftRequest;
use Molitor\Stock\Http\Requests\UpdateStockMovementDraftRequest;
use Molitor\Stock\Http\Resources\StockMovementResource;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Models\StockMovementItem;
use Molitor\Stock\Models\WarehouseRegion;
use Molitor\Stock\Repositories\StockRepositoryInterface;

class StockMovementApiController extends Controller
{
    use HasAdminFilters;

    public function __construct(
        protected StockRepositoryInterface $stockRepository,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockMovement::query()
            ->with(['warehouse:id,name', 'user:id,name'])
            ->withCount('stockMovementItems as items_count');

        $movements = $this->applyAdminFilters($query, $request, ['description'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => StockMovementResource::collection($movements->items()),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(): JsonResponse
    {
        return response()->json($this->buildFormData());
    }

    public function store(StoreStockMovementDraftRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = StockMovementType::from($validated['type']);
        $warehouseId = $this->resolveWarehouseIdFromItems($validated['items']);

        $stockMovement = DB::transaction(function () use ($validated, $type, $warehouseId): StockMovement {
            $stockMovement = StockMovement::query()->create([
                'type' => $type,
                'warehouse_id' => $warehouseId,
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                StockMovementItem::query()->create([
                    'stock_movement_id' => $stockMovement->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'warehouse_region_id' => (int) $item['warehouse_region_id'],
                    'destination_warehouse_region_id' => isset($item['destination_warehouse_region_id'])
                        ? (int) $item['destination_warehouse_region_id']
                        : null,
                ]);
            }

            return $stockMovement;
        });

        $stockMovement->load(['warehouse:id,name', 'user:id,name', 'stockMovementItems.product', 'stockMovementItems.warehouseRegion.warehouse', 'stockMovementItems.destinationWarehouseRegion.warehouse']);

        return response()->json([
            'data' => new StockMovementResource($stockMovement),
            'message' => 'A készletmozgás sikeresen rögzítve lett.',
        ], 201);
    }

    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load(['warehouse:id,name', 'user:id,name', 'stockMovementItems.product', 'stockMovementItems.warehouseRegion.warehouse', 'stockMovementItems.destinationWarehouseRegion.warehouse']);

        return response()->json([
            'data' => new StockMovementResource($stockMovement),
        ]);
    }

    public function edit(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load(['stockMovementItems.product', 'stockMovementItems.warehouseRegion.warehouse', 'stockMovementItems.destinationWarehouseRegion.warehouse']);

        return response()->json([
            'data' => new StockMovementResource($stockMovement),
            ...$this->buildFormData(),
        ]);
    }

    public function update(UpdateStockMovementDraftRequest $request, StockMovement $stockMovement): JsonResponse
    {
        if ($stockMovement->closed_at !== null) {
            return response()->json(['message' => 'A lezárt készletmozgás nem szerkeszthető.'], 422);
        }

        $validated = $request->validated();
        $type = StockMovementType::from($validated['type']);
        $warehouseId = $this->resolveWarehouseIdFromItems($validated['items']);

        DB::transaction(function () use ($validated, $type, $warehouseId, $stockMovement): void {
            $stockMovement->update([
                'type' => $type,
                'warehouse_id' => $warehouseId,
                'description' => $validated['description'] ?? null,
            ]);

            $stockMovement->stockMovementItems()->delete();

            foreach ($validated['items'] as $item) {
                StockMovementItem::query()->create([
                    'stock_movement_id' => $stockMovement->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'warehouse_region_id' => (int) $item['warehouse_region_id'],
                    'destination_warehouse_region_id' => isset($item['destination_warehouse_region_id'])
                        ? (int) $item['destination_warehouse_region_id']
                        : null,
                ]);
            }
        });

        $stockMovement->load(['warehouse:id,name', 'user:id,name', 'stockMovementItems.product', 'stockMovementItems.warehouseRegion.warehouse', 'stockMovementItems.destinationWarehouseRegion.warehouse']);

        return response()->json([
            'data' => new StockMovementResource($stockMovement),
            'message' => 'A készletmozgás sikeresen frissítve lett.',
        ]);
    }

    public function destroy(StockMovement $stockMovement): JsonResponse
    {
        if ($stockMovement->closed_at !== null) {
            return response()->json(['message' => 'A lezárt készletmozgás nem törölhető.'], 422);
        }

        DB::transaction(function () use ($stockMovement): void {
            $stockMovement->stockMovementItems()->delete();
            $stockMovement->delete();
        });

        return response()->json(['message' => 'A készletmozgás sikeresen törölve lett.']);
    }

    public function execute(StockMovement $stockMovement): JsonResponse
    {
        if ($stockMovement->closed_at !== null) {
            return response()->json(['message' => 'A készletmozgás már végre lett hajtva.'], 422);
        }

        $stockMovement->load(['stockMovementItems.product', 'stockMovementItems.warehouseRegion', 'stockMovementItems.destinationWarehouseRegion']);

        $type = $stockMovement->type;

        foreach ($stockMovement->stockMovementItems as $item) {
            $product = $item->product;
            $sourceRegion = $item->warehouseRegion;

            if (in_array($type, [StockMovementType::Out, StockMovementType::Transfer], true)) {
                $currentQuantity = $this->stockRepository->getQuantity($sourceRegion, $product);
                if ($currentQuantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'items' => [sprintf(
                            'Nincs elegendő készlet a(z) %s termékből (%s régióban). Elérhető: %s, szükséges: %s.',
                            $product->sku,
                            $sourceRegion->name,
                            $currentQuantity,
                            $item->quantity,
                        )],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($stockMovement, $type): void {
            foreach ($stockMovement->stockMovementItems as $item) {
                $product = $item->product;
                $sourceRegion = $item->warehouseRegion;
                $quantity = (int) $item->quantity;

                if ($type === StockMovementType::In) {
                    $current = $this->stockRepository->getQuantity($sourceRegion, $product);
                    $this->stockRepository->setQuantity($sourceRegion, $product, $current + $quantity);
                } elseif ($type === StockMovementType::Out) {
                    $current = $this->stockRepository->getQuantity($sourceRegion, $product);
                    $this->stockRepository->setQuantity($sourceRegion, $product, $current - $quantity);
                } elseif ($type === StockMovementType::Transfer && $item->destinationWarehouseRegion !== null) {
                    $destinationRegion = $item->destinationWarehouseRegion;
                    $sourceCurrent = $this->stockRepository->getQuantity($sourceRegion, $product);
                    $this->stockRepository->setQuantity($sourceRegion, $product, $sourceCurrent - $quantity);
                    $destCurrent = $this->stockRepository->getQuantity($destinationRegion, $product);
                    $this->stockRepository->setQuantity($destinationRegion, $product, $destCurrent + $quantity);
                }
            }

            $stockMovement->forceFill(['closed_at' => now()])->save();
        });

        $stockMovement->load(['warehouse:id,name', 'user:id,name', 'stockMovementItems.product', 'stockMovementItems.warehouseRegion.warehouse', 'stockMovementItems.destinationWarehouseRegion.warehouse']);

        return response()->json([
            'data' => new StockMovementResource($stockMovement),
            'message' => 'A készletmozgás sikeresen végrehajtva lett.',
        ]);
    }

    private function buildFormData(): array
    {
        $movementTypes = collect(StockMovementType::cases())
            ->map(static fn (StockMovementType $t): array => ['value' => $t->value, 'label' => $t->label()])
            ->values();

        $warehouseRegions = WarehouseRegion::query()
            ->with('warehouse:id,name')
            ->orderBy('name')
            ->get()
            ->map(static fn (WarehouseRegion $r): array => [
                'id' => $r->id,
                'warehouse_id' => $r->warehouse_id,
                'name' => $r->name,
                'warehouse_name' => $r->warehouse?->name,
                'label' => sprintf('%s / %s', $r->warehouse?->name ?? '-', $r->name),
            ])
            ->values();

        $products = Product::query()
            ->select(['id', 'sku'])
            ->orderBy('sku')
            ->limit(500)
            ->get()
            ->map(static fn (Product $p): array => ['id' => $p->id, 'sku' => $p->sku, 'label' => $p->sku])
            ->values();

        return [
            'movement_types' => $movementTypes,
            'warehouse_regions' => $warehouseRegions,
            'products' => $products,
        ];
    }

    /**
     * @param  array<int, array{warehouse_region_id: int|string}>  $items
     */
    private function resolveWarehouseIdFromItems(array $items): int
    {
        $firstRegionId = (int) ($items[0]['warehouse_region_id'] ?? 0);
        $region = WarehouseRegion::query()->find($firstRegionId);

        return $region?->warehouse_id ?? 0;
    }
}
