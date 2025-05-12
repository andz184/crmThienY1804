<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\LogHelper; // Assuming you have this for logging
use Illuminate\Support\Facades\Log; // Import Log facade

class CategoryController extends Controller
{
    public function __construct()
    {
        // Add permissions as needed, e.g.:
        // $this->middleware('permission:categories.view')->only(['index', 'show']);
        // $this->middleware('permission:categories.create')->only(['create', 'store']);
        // $this->middleware('permission:categories.edit')->only(['edit', 'update']);
        // $this->middleware('permission:categories.delete')->only(['destroy']);
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

        $category = Category::create($validated);
        LogHelper::log('category_create', $category, null, $category->toArray());

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
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
        $parentCategories = Category::whereNull('parent_id')->where('id', '!=', $category->id)->orderBy('name')->pluck('name', 'id');
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

        $oldData = $category->toArray();
        $category->update($validated);
        LogHelper::log('category_update', $category, $oldData, $category->fresh()->toArray());

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
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
        try {
            $category->delete();
            LogHelper::log('category_delete', $category, $oldData, null);
            return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            Log::error("Error deleting category {$category->id}: " . $e->getMessage());
            return redirect()->route('categories.index')->with('error', 'Error deleting category. Check logs.');
        }
    }
}
