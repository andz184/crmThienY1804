<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PancakeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\LogHelper; // Assuming you have this for logging
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:categories.view')->only(['index', 'show']);
        $this->middleware('permission:categories.create')->only(['create', 'store']);
        $this->middleware('permission:categories.edit')->only(['edit', 'update']);
        $this->middleware('permission:categories.delete')->only(['destroy']);
        $this->middleware('permission:categories.sync')->only(['sync', 'syncFromPancake']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('categories.view'); // Or use middleware
        $query = Category::with('parent')->withCount('products');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'like', '%' . $searchTerm . '%');
            });
        }

        $categories = $query->orderBy('name')->paginate(15);
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $this->authorize('categories.create');
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->pluck('name', 'id');
        return view('categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('categories.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $validated['is_active'] = $request->has('is_active');

        DB::beginTransaction();
        try {
            $category = Category::create($validated);
            LogHelper::log('category_create', $category, null, $category->toArray());
            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating category: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo danh mục.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        // $this->authorize('categories.view');
        $category->load('products', 'children.children'); // Load products and sub-categories
        return view('categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        // $this->authorize('categories.edit');
        $parentCategories = Category::where('id', '!=', $category->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->pluck('name', 'id');
        return view('categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        // $this->authorize('categories.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $validated['is_active'] = $request->has('is_active');

        DB::beginTransaction();
        try {
            $oldData = $category->toArray();
            $category->update($validated);
            LogHelper::log('category_update', $category, $oldData, $category->fresh()->toArray());
            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating category: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật danh mục.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // $this->authorize('categories.delete');
        // Consider checking if category has products or sub-categories before deletion
        if ($category->products()->count() > 0 || $category->children()->count() > 0) {
            return redirect()->route('categories.index')->with('error', 'Cannot delete category. It has associated products or sub-categories.');
        }
        $oldData = $category->toArray();
        DB::beginTransaction();
        try {
            $category->delete();
            LogHelper::log('category_delete', $category, $oldData, null);
            DB::commit();
            return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category: ' . $e->getMessage());
            return redirect()->route('categories.index')->with('error', 'Error deleting category. Check logs.');
        }
    }

    public function sync()
    {
        try {
            $response = Http::withHeaders([
                'api_key' => config('pancake.api_key')
            ])->get(config('pancake.base_uri') . '/shops/' . config('pancake.shop_id') . '/categories');

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể kết nối với Pancake API'
                ], $response->status());
            }

            $categories = $response->json()['data'] ?? [];
            $stats = [
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'total' => count($categories)
            ];

            DB::beginTransaction();
            try {
                foreach ($categories as $categoryData) {
                    $this->syncCategory($categoryData, null, $stats);
                }
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => sprintf(
                        'Đồng bộ thành công. Tạo mới: %d, Cập nhật: %d, Lỗi: %d',
                        $stats['created'],
                        $stats['updated'],
                        $stats['errors']
                    ),
                    'stats' => $stats
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error syncing categories: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đồng bộ danh mục'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in category sync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    private function syncCategory($categoryData, $parentId = null, &$stats)
    {
        try {
            $pancakeCategory = PancakeCategory::updateOrCreate(
                ['pancake_id' => $categoryData['id']],
                [
                    'name' => $categoryData['name'],
                    'pancake_parent_id' => $parentId,
                    'level' => $categoryData['level'] ?? 0,
                    'status' => $categoryData['status'] ?? 'active',
                    'description' => $categoryData['description'] ?? null,
                    'image_url' => $categoryData['image_url'] ?? null,
                    'api_response' => $categoryData
                ]
            );

            // Create or update corresponding local category
            $category = Category::updateOrCreate(
                ['pancake_id' => $categoryData['id']],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'] ?? null,
                    'parent_id' => $parentId,
                    'is_active' => ($categoryData['status'] ?? 'active') === 'active'
                ]
            );

            if ($category->wasRecentlyCreated) {
                $stats['created']++;
            } else if ($category->wasChanged()) {
                $stats['updated']++;
            }

            // Recursively sync child categories
            if (!empty($categoryData['children']) && is_array($categoryData['children'])) {
                foreach ($categoryData['children'] as $childCategory) {
                    $this->syncCategory($childCategory, $category->id, $stats);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing category: ' . $e->getMessage(), [
                'category_data' => $categoryData
            ]);
            $stats['errors']++;
        }
    }
}
