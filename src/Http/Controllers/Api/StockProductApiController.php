<?php

declare(strict_types=1);

namespace Molitor\Stock\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Molitor\Product\Models\Product;
use Molitor\Product\Models\ProductImage;
use Molitor\Stock\Models\Stock;
use Molitor\Stock\Models\Warehouse;
use Molitor\Stock\Models\WarehouseRegion;

class StockProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'sku');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'sku', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'sku';
        }

        $canLoadProductUnits = $this->canLoadProductUnits();
        $canLoadProductImages = $this->canLoadProductImages();

        $productQuery = Product::query()
            ->select(['id', 'sku', 'product_unit_id'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('sku', 'like', '%'.$search.'%');
            })
            ->orderBy($sort, $direction);

        if ($canLoadProductUnits) {
            $productQuery->with('productUnit');
        }

        if ($canLoadProductImages) {
            $productQuery->with('mainImage');
        }

        $products = $productQuery
            ->paginate(10)
            ->withQueryString();

        $warehouses = $this->getWarehouses();

        $productIds = collect($products->items())
            ->pluck('id')
            ->all();

        $quantitiesByProduct = $this->getQuantitiesByProductIds($productIds);

        $data = collect($products->items())
            ->map(function (Product $product) use ($warehouses, $quantitiesByProduct, $canLoadProductUnits, $canLoadProductImages): array {
                return $this->transformProductSummary($product, $warehouses, $quantitiesByProduct, $canLoadProductUnits, $canLoadProductImages);
            })
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $canLoadProductUnits = $this->canLoadProductUnits();
        $canLoadProductImages = $this->canLoadProductImages();

        if ($canLoadProductUnits) {
            $product->loadMissing('productUnit');
        }

        if ($canLoadProductImages) {
            $product->loadMissing('mainImage');
        }

        $warehouses = $this->getWarehouses();
        $quantitiesByProduct = $this->getQuantitiesByProductIds([(int) $product->id]);

        return response()->json([
            'data' => $this->buildProductDetail($product, $warehouses, $quantitiesByProduct, $canLoadProductUnits, $canLoadProductImages),
        ]);
    }

    protected function getWarehouses(): Collection
    {
        return Warehouse::query()
            ->with(['regions' => function ($query): void {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array<int, float>>
     */
    protected function getQuantitiesByProductIds(array $productIds): array
    {
        $quantitiesByProduct = [];

        if ($productIds === []) {
            return $quantitiesByProduct;
        }

        $stockRows = Stock::query()
            ->selectRaw('product_id, warehouse_region_id, SUM(quantity) as quantity')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id', 'warehouse_region_id')
            ->get();

        foreach ($stockRows as $stockRow) {
            $productId = (int) $stockRow->product_id;
            $warehouseRegionId = (int) $stockRow->warehouse_region_id;
            $quantitiesByProduct[$productId][$warehouseRegionId] = (float) $stockRow->quantity;
        }

        return $quantitiesByProduct;
    }

    /**
     * @param  array<int, array<int, float>>  $quantitiesByProduct
     * @return array<string, mixed>
     */
    protected function transformProductSummary(
        Product $product,
        Collection $warehouses,
        array $quantitiesByProduct,
        bool $canLoadProductUnits,
        bool $canLoadProductImages
    ): array
    {
        $warehouseData = $this->buildWarehouseData($product, $warehouses, $quantitiesByProduct);

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'main_image_url' => $this->resolveMainImageUrl($product, $canLoadProductImages),
            'quantity_unit' => $this->resolveProductUnitLabel($product, $canLoadProductUnits),
            'total_quantity' => collect($warehouseData)->sum('total_quantity'),
            'region_quantities' => collect($warehouseData)
                ->flatMap(fn (array $warehouse): array => collect($warehouse['regions'])->all())
                ->values(),
        ];
    }

    /**
     * @param  array<int, array<int, float>>  $quantitiesByProduct
     * @return array<string, mixed>
     */
    protected function buildProductDetail(
        Product $product,
        Collection $warehouses,
        array $quantitiesByProduct,
        bool $canLoadProductUnits,
        bool $canLoadProductImages
    ): array
    {
        $warehouseData = $this->buildWarehouseData($product, $warehouses, $quantitiesByProduct);
        $regionData = collect($warehouseData)
            ->flatMap(fn (array $warehouse): array => collect($warehouse['regions'])->all())
            ->values();

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'main_image_url' => $this->resolveMainImageUrl($product, $canLoadProductImages),
            'quantity_unit' => $this->resolveProductUnitLabel($product, $canLoadProductUnits),
            'total_quantity' => collect($warehouseData)->sum('total_quantity'),
            'warehouse_count' => count($warehouseData),
            'region_count' => $regionData->count(),
            'warehouses' => $warehouseData,
            'regions' => $regionData,
        ];
    }

    protected function canLoadProductImages(): bool
    {
        return Schema::hasTable('product_images');
    }

    protected function canLoadProductUnits(): bool
    {
        return Schema::hasTable('product_units') && Schema::hasTable('product_unit_translations');
    }

    protected function resolveProductUnitLabel(Product $product, bool $canLoadProductUnits): ?string
    {
        if (! $canLoadProductUnits || ! $product->relationLoaded('productUnit')) {
            return null;
        }

        $productUnit = $product->productUnit;
        if ($productUnit === null) {
            return null;
        }

        $name = trim((string) $productUnit->name);
        if ($name !== '') {
            return $name;
        }

        $code = trim((string) $productUnit->code);

        return $code !== '' ? $code : null;
    }

    protected function resolveMainImageUrl(Product $product, bool $canLoadProductImages): ?string
    {
        if (! $canLoadProductImages || ! $product->relationLoaded('mainImage')) {
            return null;
        }

        $mainImage = $product->mainImage;
        if (! $mainImage instanceof ProductImage) {
            return null;
        }

        return $mainImage->getSrc();
    }

    /**
     * @param  array<int, array<int, float>>  $quantitiesByProduct
     * @return array<int, array<string, mixed>>
     */
    protected function buildWarehouseData(Product $product, Collection $warehouses, array $quantitiesByProduct): array
    {
        return $warehouses
            ->map(function (Warehouse $warehouse) use ($product, $quantitiesByProduct): array {
                $regions = $warehouse->regions
                    ->map(function (WarehouseRegion $warehouseRegion) use ($product, $quantitiesByProduct, $warehouse): array {
                        $quantity = $quantitiesByProduct[$product->id][$warehouseRegion->id] ?? 0.0;

                        return [
                            'warehouse_region_id' => $warehouseRegion->id,
                            'warehouse_region_name' => $warehouseRegion->name,
                            'warehouse_name' => $warehouse->name,
                            'label' => trim((string) $warehouseRegion),
                            'quantity' => $quantity,
                        ];
                    })
                    ->values();

                return [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                    'total_quantity' => $regions->sum('quantity'),
                    'regions' => $regions,
                ];
            })
            ->values()
            ->all();
    }
}




