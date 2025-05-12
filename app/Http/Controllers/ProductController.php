<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // For transactions
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct()
    {
        // Add permissions as needed, e.g.:
        // $this->middleware('permission:products.view')->only(['index', 'show']);
        // $this->middleware('permission:products.create')->only(['create', 'store']);
        // $this->middleware('permission:products.edit')->only(['edit', 'update', 'variations', 'storeVariation', 'updateVariation', 'destroyVariation']);
        // $this->middleware('permission:products.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('products.view');
        $query = Product::with('category', 'variations');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('variations', function($vq) use ($searchTerm) {
                      $vq->where('sku', 'like', '%' . $searchTerm . '%');
                  });
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->paginate(15);
        $categories = Category::orderBy('name')->pluck('name', 'id');

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $this->authorize('products.create');
        $categories = Category::orderBy('name')->pluck('name', 'id');
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('products.create');
        $validatedProduct = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'base_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if (empty($validatedProduct['slug'])) {
            $validatedProduct['slug'] = Str::slug($validatedProduct['name']);
        }
        $validatedProduct['is_active'] = $request->has('is_active');

        DB::beginTransaction();
        try {
            $product = Product::create($validatedProduct);

            // Handle initial variations if provided (example structure)
            if ($request->has('variations')) {
                foreach ($request->input('variations') as $variationData) {
                    if (!empty($variationData['sku']) && !empty($variationData['name']) && isset($variationData['price']) && isset($variationData['stock_quantity'])) {
                        $variationData['product_id'] = $product->id;
                        $variationData['is_active'] = isset($variationData['is_active']);
                        ProductVariation::create($variationData);
                    }
                }
            }

            LogHelper::log('product_create', $product, null, $product->load('variations')->toArray());
            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating product: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Error creating product. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // $this->authorize('products.view');
        $product->load('category', 'variations');
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        // $this->authorize('products.edit');
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $product->load('variations');
        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // $this->authorize('products.edit');
        $validatedProduct = $request->validate([
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'base_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if (empty($validatedProduct['slug'])) {
            $validatedProduct['slug'] = Str::slug($validatedProduct['name']);
        }
        $validatedProduct['is_active'] = $request->has('is_active');

        DB::beginTransaction();
        try {
            $oldData = $product->load('variations')->toArray(); // Get old data with variations for logging
            $product->update($validatedProduct);

            // Basic example: Sync variations - delete existing and add new ones
            // A more sophisticated approach would update existing, delete removed, add new.
            // This simple sync is for demonstration.
            // $product->variations()->delete(); // Be careful with this in production, might be too destructive
            // if ($request->has('variations')) {
            //     foreach ($request->input('variations') as $variationData) {
            //         if (!empty($variationData['sku']) && !empty($variationData['name']) && isset($variationData['price']) && isset($variationData['stock_quantity'])) {
            //             $variationData['product_id'] = $product->id;
            //             $variationData['is_active'] = isset($variationData['is_active']);
            //             ProductVariation::create($variationData);
            //         }
            //     }
            // }
            // For a more robust variation update, you would typically handle it via separate AJAX calls or a more detailed form section on the product edit page.
            // See dedicated variation management methods below for a better approach.

            LogHelper::log('product_update', $product, $oldData, $product->fresh()->load('variations')->toArray());
            DB::commit();
            return redirect()->route('products.edit', $product)->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating product {$product->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Error updating product. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // $this->authorize('products.delete');
        DB::beginTransaction();
        try {
            // Orders might be linked to variations of this product.
            // Decide on deletion policy: disallow, soft delete, or nullify links.
            // For now, we check if any variation has associated orders.
            $hasOrders = $product->variations()->whereHas('orders')->exists();
            if ($hasOrders) {
                DB::rollBack();
                return redirect()->route('products.index')->with('error', 'Cannot delete product. It has orders associated with its variations.');
            }

            $oldData = $product->load('variations')->toArray();
            $product->variations()->delete(); // Delete variations first
            $product->delete(); // Then delete product

            LogHelper::log('product_delete', $product, $oldData, null); // Product itself is deleted, $product might not be ideal for first arg
            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product and its variations deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting product {$product->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('products.index')->with('error', 'Error deleting product. Check logs.');
        }
    }

    // --- Product Variation Management --- //
    // These methods would typically be called via AJAX from the product edit page.

    /**
     * Display a form to add a variation to a product (can be part of product edit page or a modal).
     * Or list variations for a product.
     */
    public function variations(Product $product)
    {
        // $this->authorize('products.edit'); // Or a more specific variations.manage permission
        $product->load('variations');
        return view('products.variations.index', compact('product')); // Assuming a variations sub-view
    }

    /**
     * Show the form for editing the specified variation.
     */
    public function editVariation(Product $product, ProductVariation $variation)
    {
        // $this->authorize('products.edit');
        if ($variation->product_id !== $product->id) {
            abort(403, 'Variation does not belong to this product.');
        }
        // This view would typically be a modal or a dedicated small form
        return view('products.variations.edit', compact('product', 'variation'));
    }

    /**
     * Store a newly created variation for a product.
     */
    public function storeVariation(Request $request, Product $product)
    {
        // $this->authorize('products.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:product_variations,sku',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
            // Add validation for any custom attributes if using a JSON field
        ]);

        $validated['product_id'] = $product->id;
        $validated['is_active'] = $request->has('is_active');

        $variation = ProductVariation::create($validated);
        // Log this action if needed

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Variation added.', 'variation' => $variation]);
        }
        return redirect()->route('products.edit', $product)->with('success', 'Variation added successfully.');
    }

    /**
     * Update the specified variation.
     */
    public function updateVariation(Request $request, Product $product, ProductVariation $variation)
    {
        // $this->authorize('products.edit');
        if ($variation->product_id !== $product->id) {
            abort(403, 'Variation does not belong to this product.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:product_variations,sku,' . $variation->id,
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->has('is_active');

        $variation->update($validated);
        // Log this action if needed

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Variation updated.', 'variation' => $variation]);
        }
        return redirect()->route('products.edit', $product)->with('success', 'Variation updated successfully.');
    }

    /**
     * Remove the specified variation from storage.
     */
    public function destroyVariation(Request $request, Product $product, ProductVariation $variation)
    {
        // $this->authorize('products.edit');
        if ($variation->product_id !== $product->id) {
            abort(403, 'Variation does not belong to this product.');
        }

        // Check if variation has orders
        if ($variation->orders()->exists()) {
            $message = 'Cannot delete variation. It is associated with existing orders.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('products.edit', $product)->with('error', $message);
        }

        $variation->delete();
        // Log this action if needed

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Variation deleted.']);
        }
        return redirect()->route('products.edit', $product)->with('success', 'Variation deleted successfully.');
    }
}
