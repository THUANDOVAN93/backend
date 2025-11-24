<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['categories', 'images']);

        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->has('search')) {

            $search = Str::lower($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $status = $request->status;

            switch ($status) {
                case 'active':
                    $query->where('is_active', true);
                    break;

                case 'inactive':
                    $query->where('is_active', false);
                    break;

                case 'low_stock':
                    // Products where stock <= threshold AND stock > 0
                    $query->where('track_inventory', true)
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                        ->where('stock_quantity', '>', 0);
                    break;

                case 'out_of_stock':
                    // Products with stock = 0
                    $query->where('track_inventory', true)
                        ->where('stock_quantity', '=', 0);
                    break;

                case 'in_stock':
                    // Products with good stock (> threshold) OR not tracking inventory
                    $query->where(function ($q) {
                        $q->where('track_inventory', false)
                            ->orWhere(function ($q2) {
                                $q2->where('track_inventory', true)
                                    ->whereColumn('stock_quantity', '>', 'low_stock_threshold');
                            });
                    });
                    break;
            }
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('featured') && $request->featured) {
            $query->where('is_featured', true);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'featured' => Product::where('is_featured', true)->count(),

            'out_of_stock' => Product::where('track_inventory', true)
                ->where('stock_quantity', 0)
                ->count(),

            'low_stock' => Product::where('track_inventory', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->where('stock_quantity', '>', 0)
                ->count(),

            'in_stock' => Product::where(function ($q) {
                $q->where('track_inventory', false)
                    ->orWhere(function ($q2) {
                        $q2->where('track_inventory', true)
                            ->whereColumn('stock_quantity', '>', 'low_stock_threshold');
                    });
            })->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['categories', 'images'])
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            'sku' => 'required|string|unique:products,sku',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'images' => 'nullable|array',
        ]);

        $product = Product::create($validated);

        if (isset($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product->load(['categories', 'images'])),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:products,slug,' . $product->id,
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $product->update($validated);

        if (isset($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->load(['categories', 'images'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
