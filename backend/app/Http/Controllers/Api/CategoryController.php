<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        // Filter by parent (get only root categories or children)
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Include children and products count
        if ($request->boolean('with_children')) {
            $query->with('children');
        }

        if ($request->boolean('with_products_count')) {
            $query->withCount('products');
        }

        // Sort
        $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');

        // Paginate or get all
        if ($request->boolean('paginate', true)) {
            $categories = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'data' => CategoryResource::collection($categories),
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                ],
            ]);
        } else {
            $categories = $query->get();

            return response()->json([
                'data' => CategoryResource::collection($categories),
            ]);
        }
    }

    /**
     * Display the specified category
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->with(['children', 'parent'])
            ->withCount('products')
            ->firstOrFail();

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure slug is unique
            $originalSlug = $validated['slug'];
            $count = 1;
            while (Category::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category->load(['parent', 'children'])),
        ], 201);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Prevent category from being its own parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return response()->json([
                'message' => 'A category cannot be its own parent',
            ], 422);
        }

        // Prevent circular relationships
        if (isset($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            if ($parent && $this->isDescendant($category, $parent)) {
                return response()->json([
                    'message' => 'Cannot set a descendant category as parent (circular reference)',
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category->load(['parent', 'children'])),
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with associated products',
            ], 422);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Delete or reassign subcategories first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Get category tree (hierarchical structure)
     */
    public function tree(): JsonResponse
    {
        $categories = Category::with(['children' => function ($query) {
            $query->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->with('children');
        }])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get products for a specific category
     */
    public function products(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $query = $category->products()
            ->with(['categories', 'images'])
            ->where('is_active', true);

        // Include subcategory products
        if ($request->boolean('include_subcategories', true)) {
            $categoryIds = $this->getAllDescendantIds($category);
            $categoryIds[] = $category->id;

            $query = \App\Models\Product::with(['categories', 'images'])
                ->where('is_active', true)
                ->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => \App\Http\Resources\ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'category' => new CategoryResource($category),
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return response()->json([
            'message' => 'Categories reordered successfully',
        ]);
    }

    /**
     * Check if a category is a descendant of another
     */
    private function isDescendant(Category $category, Category $potentialAncestor): bool
    {
        $parent = $potentialAncestor->parent;

        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Get all descendant category IDs
     */
    private function getAllDescendantIds(Category $category): array
    {
        $ids = [];

        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
    }
}
