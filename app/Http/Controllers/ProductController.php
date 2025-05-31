<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // For transactions
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    protected $baseUri;
    protected $apiKey;
    protected $shopId;

    public function __construct()
    {
        $this->baseUri = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1/'), '/');
        $this->apiKey = config('pancake.api_key');
        $this->shopId = config('pancake.shop_id');

        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.view')->only(['index', 'show']);
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.create')->only(['create', 'store']);
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit')->only(['edit', 'update', 'variations', 'storeVariation', 'updateVariation', 'destroyVariation']);
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.delete')->only(['destroy']);
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.sync')->only(['syncFromPancake', 'pushToPancake', 'updateInventory']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('products.view');

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

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('products.create');

        $categories = Category::orderBy('name')->pluck('name', 'id');
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('products.create');

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
            return redirect()->route('admin.products.index')->with('success', 'Sản phẩm đã được tạo thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating product: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo sản phẩm. Vui lòng thử lại.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $this->authorize('products.view');

        $product->load('category', 'variations');
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $this->authorize('products.edit');

        $categories = Category::orderBy('name')->pluck('name', 'id');
        $product->load('variations');
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('products.edit');

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
            $oldData = $product->load('variations')->toArray();
            $product->update($validatedProduct);

            LogHelper::log('product_update', $product, $oldData, $product->fresh()->load('variations')->toArray());
            DB::commit();
            return redirect()->route('admin.products.edit', $product)->with('success', 'Sản phẩm đã được cập nhật thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating product {$product->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật sản phẩm. Vui lòng thử lại.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->authorize('products.delete');

        DB::beginTransaction();
        try {
            $hasOrders = $product->variations()->whereHas('orders')->exists();
            if ($hasOrders) {
                DB::rollBack();
                return redirect()->route('admin.products.index')->with('error', 'Không thể xóa sản phẩm. Sản phẩm đã có đơn hàng.');
            }

            $oldData = $product->load('variations')->toArray();
            $product->variations()->delete();
            $product->delete();

            LogHelper::log('product_delete', $product, $oldData, null);
            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Sản phẩm đã được xóa thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting product {$product->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('admin.products.index')->with('error', 'Có lỗi xảy ra khi xóa sản phẩm.');
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
        $product->load('variations');
        return view('products.variations.index', compact('product')); // Assuming a variations sub-view
    }

    /**
     * Show the form for editing the specified variation.
     */
    public function editVariation(Product $product, ProductVariation $variation)
    {
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

    /**
     * Get products from Pancake API
     */
    public function getProductsFromPancake(Request $request)
    {
        $this->authorize('products.sync');

        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            $response = Http::get("{$this->baseUri}/shops/{$this->shopId}/products", [
                'api_key' => $this->apiKey,
                'page_size' => $request->input('page_size', 100),
                'page_number' => $request->input('page', 1),
                'search' => $request->input('search'),
                'category_id' => $request->input('category_id'),
                'is_active' => $request->input('is_active', true)
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch products from Pancake', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch products from Pancake API'
                ], $response->status());
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error('Error fetching products from Pancake: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new product in Pancake
     */
    public function createProductInPancake(Request $request)
    {
        $this->authorize('products.create');

        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            // Validate request
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:50|unique:products',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:categories,id',
                'variations' => 'required|array|min:1',
                'variations.*.sku' => 'required|string|max:50|distinct',
                'variations.*.name' => 'required|string|max:255',
                'variations.*.retail_price' => 'required|numeric|min:0',
                'variations.*.cost' => 'required|numeric|min:0',
                'variations.*.stock' => 'required|integer|min:0'
            ]);

            // Prepare product data
            $productData = [
                'name' => $request->name,
                'sku' => $request->sku,
                'description' => $request->description,
                'category_ids' => [$request->category_id],
                'variations' => $request->variations
            ];

            // Send to Pancake API
            $response = Http::post("{$this->baseUri}/shops/{$this->shopId}/products", [
                'api_key' => $this->apiKey,
                'product' => $productData
            ]);

            if (!$response->successful()) {
                Log::error('Failed to create product in Pancake', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create product in Pancake API'
                ], $response->status());
            }

            // Create product in local database
            DB::beginTransaction();
            try {
                $pancakeProduct = $response->json()['data'];

                $product = Product::create([
                    'name' => $request->name,
                    'sku' => $request->sku,
                    'description' => $request->description,
                    'category_id' => $request->category_id,
                    'pancake_id' => $pancakeProduct['id'],
                    'is_active' => true,
                    'metadata' => [
                        'pancake_data' => $pancakeProduct,
                        'last_sync' => now()
                    ]
                ]);

                // Create variations
                foreach ($request->variations as $variationData) {
                    $product->variations()->create([
                        'name' => $variationData['name'],
                        'sku' => $variationData['sku'],
                        'price' => $variationData['retail_price'],
                        'cost' => $variationData['cost'],
                        'stock' => $variationData['stock'],
                        'pancake_variant_id' => $variationData['id'] ?? null
                    ]);
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'data' => $product
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error creating product locally: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating product in local database: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product in Pancake
     */
    public function updateProductInPancake(Request $request, Product $product)
    {
        $this->authorize('products.edit');

        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            // Validate request
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
                'description' => 'nullable|string',
                'category_id' => 'required|exists:categories,id',
                'variations' => 'required|array|min:1',
                'variations.*.sku' => 'required|string|max:50|distinct',
                'variations.*.name' => 'required|string|max:255',
                'variations.*.retail_price' => 'required|numeric|min:0',
                'variations.*.cost' => 'required|numeric|min:0',
                'variations.*.stock' => 'required|integer|min:0'
            ]);

            // Prepare product data
            $productData = [
                'name' => $request->name,
                'sku' => $request->sku,
                'description' => $request->description,
                'category_ids' => [$request->category_id],
                'variations' => $request->variations
            ];

            // Send to Pancake API
            $response = Http::put("{$this->baseUri}/shops/{$this->shopId}/products/{$product->pancake_id}", [
                'api_key' => $this->apiKey,
                'product' => $productData
            ]);

            if (!$response->successful()) {
                Log::error('Failed to update product in Pancake', [
                    'product_id' => $product->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update product in Pancake API'
                ], $response->status());
            }

            // Update product in local database
            DB::beginTransaction();
            try {
                $pancakeProduct = $response->json()['data'];

                $product->update([
                    'name' => $request->name,
                    'sku' => $request->sku,
                    'description' => $request->description,
                    'category_id' => $request->category_id,
                    'metadata' => [
                        'pancake_data' => $pancakeProduct,
                        'last_sync' => now()
                    ]
                ]);

                // Update variations
                foreach ($request->variations as $variationData) {
                    $product->variations()->updateOrCreate(
                        ['pancake_variant_id' => $variationData['id']],
                        [
                            'name' => $variationData['name'],
                            'sku' => $variationData['sku'],
                            'price' => $variationData['retail_price'],
                            'cost' => $variationData['cost'],
                            'stock' => $variationData['stock']
                        ]
                    );
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'data' => $product
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating product locally: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating product in local database: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product inventory in Pancake
     */
    public function updateInventoryInPancake(Request $request, Product $product)
    {
        $this->authorize('products.edit');

        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            // Validate request
            $request->validate([
                'variations' => 'required|array',
                'variations.*.id' => 'required|exists:product_variations,id',
                'variations.*.stock' => 'required|integer|min:0',
                'warehouse_id' => 'required|exists:warehouses,id'
            ]);

            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            $inventoryUpdates = [];
            foreach ($request->variations as $variation) {
                $inventoryUpdates[] = [
                    'variation_id' => $variation['id'],
                    'warehouse_id' => $warehouse->pancake_id,
                    'stock' => $variation['stock']
                ];
            }

            // Send to Pancake API
            $response = Http::put("{$this->baseUri}/shops/{$this->shopId}/inventory", [
                'api_key' => $this->apiKey,
                'inventory' => $inventoryUpdates
            ]);

            if (!$response->successful()) {
                Log::error('Failed to update inventory in Pancake', [
                    'product_id' => $product->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update inventory in Pancake API'
                ], $response->status());
            }

            // Update local inventory
            DB::beginTransaction();
            try {
                foreach ($request->variations as $variation) {
                    $product->variations()
                        ->where('id', $variation['id'])
                        ->update(['stock' => $variation['stock']]);
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory updated successfully'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating inventory locally: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating inventory in local database: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error updating inventory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync products from Pancake
     */
    public function syncFromPancake()
    {
        $this->authorize('products.sync');

        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            $stats = [
                'created' => 0,
                'updated' => 0,
                'errors' => 0
            ];

            $page = 1;
            $hasMore = true;

            while ($hasMore) {
                $response = Http::get("{$this->baseUri}/shops/{$this->shopId}/products", [
                    'api_key' => $this->apiKey,
                    'page_size' => 100,
                    'page_number' => $page
                ]);

                if (!$response->successful()) {
                    Log::error('Failed to fetch products from Pancake', [
                        'page' => $page,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to fetch products from Pancake API'
                    ], $response->status());
                }

                $data = $response->json();

                DB::beginTransaction();
                try {
                    foreach ($data['data'] as $productData) {
                        $product = Product::updateOrCreate(
                            ['pancake_id' => $productData['id']],
                            [
                                'name' => $productData['name'],
                                'sku' => $productData['sku'] ?? null,
                                'description' => $productData['description'] ?? null,
                                'is_active' => !($productData['is_removed'] ?? false),
                                'metadata' => [
                                    'pancake_data' => $productData,
                                    'last_sync' => now()
                                ]
                            ]
                        );

                        if ($product->wasRecentlyCreated) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }

                        // Process variations
                        if (!empty($productData['variations'])) {
                            foreach ($productData['variations'] as $variationData) {
                                $product->variations()->updateOrCreate(
                                    ['pancake_variant_id' => $variationData['id']],
                                    [
                                        'name' => $variationData['name'] ?? $productData['name'],
                                        'sku' => $variationData['sku'] ?? null,
                                        'price' => $variationData['retail_price'] ?? 0,
                                        'cost' => $variationData['cost'] ?? 0,
                                        'stock' => $variationData['stock'] ?? 0,
                                        'metadata' => [
                                            'pancake_data' => $variationData,
                                            'last_sync' => now()
                                        ]
                                    ]
                                );
                            }
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error processing products', [
                        'page' => $page,
                        'error' => $e->getMessage()
                    ]);
                    $stats['errors']++;
                }

                $hasMore = !empty($data['next_page']);
                $page++;
            }

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Products sync completed. Created: %d, Updated: %d, Errors: %d',
                    $stats['created'],
                    $stats['updated'],
                    $stats['errors']
                ),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error syncing products: ' . $e->getMessage()
            ], 500);
        }
    }
}
