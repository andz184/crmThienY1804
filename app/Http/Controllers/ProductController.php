<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
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
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit')->only(['edit', 'update', 'variants', 'storeVariant', 'updateVariant', 'destroyVariant']);
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.delete')->only(['destroy']);
        // $this->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.sync')->only(['syncFromPancake', 'pushToPancake', 'updateInventory']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('products.view');

        $query = Product::with('category', 'variants');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('variants', function($vq) use ($searchTerm) {
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
        $warehouses = Warehouse::where('status', true)->orderBy('name')->pluck('name', 'id');
        return view('admin.products.create', compact('categories', 'warehouses'));
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

            // Handle initial variants if provided (example structure)
            if ($request->has('variants')) {
                foreach ($request->input('variants') as $variationData) {
                    if (!empty($variationData['sku']) && !empty($variationData['name']) && isset($variationData['price']) && isset($variationData['stock_quantity'])) {
                        $variationData['product_id'] = $product->id;
                        $variationData['is_active'] = isset($variationData['is_active']);
                        ProductVariant::create($variationData);
                    }
                }
            }

            LogHelper::log('product_create', $product, null, $product->load('variants')->toArray());
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

        $product->load('category', 'variants');
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $this->authorize('products.edit');

        $categories = Category::orderBy('name')->pluck('name', 'id');
        $warehouses = Warehouse::where('status', true)->orderBy('name')->pluck('name', 'id');
        $product->load('variants');
        return view('admin.products.edit', compact('product', 'categories', 'warehouses'));
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
            $oldData = $product->load('variants')->toArray();
            $product->update($validatedProduct);

            LogHelper::log('product_update', $product, $oldData, $product->fresh()->load('variants')->toArray());
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
        // $this->authorize('products.delete');
        // Consider checking if product has orders before deletion
        try {
            DB::beginTransaction();

            // Check if product has any orders
            $hasOrders = $product->variants()->whereHas('orders')->exists();
            if ($hasOrders) {
                return redirect()->route('products.index')
                    ->with('error', 'Cannot delete product. It has associated orders.');
            }

            // Delete variants first
            $product->variants()->delete();

            // Then delete the product
            $product->delete();

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
            return redirect()->route('products.index')
                ->with('error', 'Error deleting product. Check logs.');
        }
    }

    // --- Product Variant Management --- //
    // These methods would typically be called via AJAX from the product edit page.

    /**
     * Display a form to add a variant to a product (can be part of product edit page or a modal).
     * Or list variants for a product.
     */
    public function variants(Product $product)
    {
        $product->load('variants');
        return view('products.variants.index', compact('product')); // Assuming a variants sub-view
    }

    /**
     * Show the form for editing a variant.
     */
    public function editVariant(Product $product, ProductVariant $variant)
    {
        // $this->authorize('products.edit');
        return view('products.variants.edit', compact('product', 'variant'));
    }

    /**
     * Store a new variant.
     */
    public function storeVariant(Request $request, Product $product)
    {
        // $this->authorize('products.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:product_variants,sku',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            $variant = ProductVariant::create($validated);
            DB::commit();
            return redirect()->route('products.show', $product)
                ->with('success', 'Variant created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating variant: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error creating variant. Please try again.');
        }
    }

    /**
     * Update the specified variant.
     */
    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        // $this->authorize('products.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:product_variants,sku,' . $variant->id,
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            $variant->update($validated);
            DB::commit();
            return redirect()->route('products.show', $product)
                ->with('success', 'Variant updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating variant: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error updating variant. Please try again.');
        }
    }

    /**
     * Remove the specified variant.
     */
    public function destroyVariant(Request $request, Product $product, ProductVariant $variant)
    {
        // $this->authorize('products.edit');
        if ($variant->orders()->exists()) {
            return back()->with('error', 'Cannot delete variant. It has associated orders.');
        }

        DB::beginTransaction();
        try {
            $variant->delete();
            DB::commit();
            return redirect()->route('products.show', $product)
                ->with('success', 'Variant deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting variant: ' . $e->getMessage());
            return back()->with('error', 'Error deleting variant. Please try again.');
        }
    }

    /**
     * Show the form for creating a new variant.
     */
    public function createVariant(Product $product)
    {
        // $this->authorize('products.edit');
        return view('products.variants.create', compact('product'));
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
                'variants' => 'required|array|min:1',
                'variants.*.sku' => 'required|string|max:50|distinct',
                'variants.*.name' => 'required|string|max:255',
                'variants.*.retail_price' => 'required|numeric|min:0',
                'variants.*.cost' => 'required|numeric|min:0',
                'variants.*.stock' => 'required|integer|min:0'
            ]);

            // Prepare product data
            $productData = [
                'name' => $request->name,
                'sku' => $request->sku,
                'description' => $request->description,
                'category_ids' => [$request->category_id],
                'variants' => $request->variants
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

                // Create variants
                foreach ($request->variants as $variationData) {
                    $product->variants()->create([
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
                'variants' => 'required|array|min:1',
                'variants.*.sku' => 'required|string|max:50|distinct',
                'variants.*.name' => 'required|string|max:255',
                'variants.*.retail_price' => 'required|numeric|min:0',
                'variants.*.cost' => 'required|numeric|min:0',
                'variants.*.stock' => 'required|integer|min:0'
            ]);

            // Prepare product data
            $productData = [
                'name' => $request->name,
                'sku' => $request->sku,
                'description' => $request->description,
                'category_ids' => [$request->category_id],
                'variants' => $request->variants
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

                // Update variants
                foreach ($request->variants as $variationData) {
                    $product->variants()->updateOrCreate(
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
     * Update inventory in Pancake
     */
    public function updateInventoryInPancake(Request $request, Product $product)
    {
        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            // Validate request
            $request->validate([
                'variants' => 'required|array',
                'variants.*.id' => 'required|exists:product_variants,id',
                'variants.*.stock' => 'required|integer|min:0',
                'warehouse_id' => 'required|exists:warehouses,id'
            ]);

            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            $inventoryUpdates = [];
            foreach ($request->variants as $variation) {
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
                foreach ($request->variants as $variation) {
                    $product->variants()
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
        set_time_limit(14400);
        // try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            $stats = [
                'products_created' => 0,
                'products_updated' => 0,
                'variants_created' => 0,
                'variants_updated' => 0,
                'errors' => 0,
                'pages_processed' => 0
            ];

            $page = 1;
            $totalPages = null;
            $processedPancakeIds = []; // Track processed product IDs

            while (true) {
                $response = Http::get("{$this->baseUri}/shops/{$this->shopId}/products/variations", [
                    'api_key' => $this->apiKey,
                    'page_size' => 100,
                    'page_number' => $page
                ]);

                if (!$response->successful()) {
                    Log::error('Failed to fetch products/variants from Pancake', [
                        'page' => $page,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    $stats['errors']++;
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to fetch products/variants from Pancake API on page ' . $page
                    ], $response->status());
                }

                $data = $response->json();
                $stats['pages_processed']++;

                if (!isset($data['data']) || !isset($data['total_pages']) || !is_array($data['data'])) {
                    Log::error('Pancake API response missing data/total_pages or data is not an array.', [
                        'page' => $page,
                        'response_keys' => isset($data) ? array_keys($data) : 'null',
                        'data_type' => isset($data['data']) ? gettype($data['data']) : 'not_set'
                    ]);
                    $stats['errors']++;
                    break;
                }

                if ($totalPages === null) {
                    $totalPages = (int)$data['total_pages'];
                    if ($totalPages == 0 && empty($data['data'])) {
                        break;
                    }
                }

                DB::beginTransaction();
                try {
                    // First, collect all pancake IDs from this page
                    $pancakeIds = collect($data['data'])->pluck('id')->unique()->toArray();
                    $processedPancakeIds = array_merge($processedPancakeIds, $pancakeIds);

                    // Process existing products first
                    foreach ($data['data'] as $variationPancakeData) {
                        if (!is_array($variationPancakeData) || !isset($variationPancakeData['product_id']) || !isset($variationPancakeData['id'])) {
                            Log::warning('Skipping invalid variation data from Pancake', ['variation_data' => $variationPancakeData]);
                            $stats['errors']++;
                            continue;
                        }

                        $productPancakeId = $variationPancakeData['id'];
                        $productPancakeDetails = $variationPancakeData['product'] ?? null;

                        // Find existing product
                        $product = Product::where('pancake_id', $productPancakeId)->first();

                        if ($product) {
                            // Update existing product
                            $productAttributes = [
                                'name' => $productPancakeDetails['name'] ?? $variationPancakeData['name'] ?? 'Unknown Product',
                                'sku' => $productPancakeDetails['sku'] ?? null,
                                'description' => $productPancakeDetails['description'] ?? null,
                                'is_active' => !($productPancakeDetails['is_removed'] ?? ($variationPancakeData['is_removed'] ?? false)),
                            ];

                            $product->fill($productAttributes);
                            $product->metadata = array_merge(
                                $product->metadata ?? [],
                                ($productPancakeDetails ? ['pancake_product_data' => $productPancakeDetails] : []),
                                ['last_sync_product_level' => now()]
                            );
                            $product->save();
                            $stats['products_updated']++;

                            // Update or create variants for existing product
                            try {
                                $existingVariant = $product->variants()
                                    ->where('pancake_variant_id', $variationPancakeData['id'])
                                    ->first();

                                $variationAttributes = [
                                    'name' => $variationPancakeData['name'] ?? $product->name,
                                    'sku' => $variationPancakeData['sku'] ?? $variationPancakeData['barcode'] ?? null,
                                    'price' => $variationPancakeData['retail_price'] ?? 0,
                                    'cost' => $variationPancakeData['last_imported_price'] ?? ($variationPancakeData['cost_price'] ?? 0),
                                    'stock' => $variationPancakeData['remain_quantity'] ?? ($variationPancakeData['stock'] ?? 0),
                                    'is_active' => !($variationPancakeData['is_hidden'] ?? ($variationPancakeData['is_removed'] ?? false)),
                                    'pancake_product_id' => $productPancakeId
                                ];

                                if ($existingVariant) {
                                    $existingVariant->fill($variationAttributes);
                                    $existingVariant->metadata = array_merge(
                                        $existingVariant->metadata ?? [],
                                        ['pancake_variation_data' => $variationPancakeData],
                                        ['last_sync_variation_level' => now()]
                                    );
                                    $existingVariant->save();
                                    $stats['variants_updated']++;
                                }
                            } catch (\Exception $e) {
                                Log::error('Error processing variant for existing product: ' . $e->getMessage(), [
                                    'product_id' => $product->id,
                                    'pancake_variant_id' => $variationPancakeData['id']
                                ]);
                                $stats['errors']++;
                                continue;
                            }
                        } else {
                            // Create new product
                            try {
                                $product = new Product();
                                $product->pancake_id = $productPancakeId;
                                $product->name = $productPancakeDetails['name'] ?? $variationPancakeData['name'] ?? 'Unknown Product';
                                $product->sku = $productPancakeDetails['sku'] ?? null;
                                $product->description = $productPancakeDetails['description'] ?? null;
                                $product->is_active = !($productPancakeDetails['is_removed'] ?? ($variationPancakeData['is_removed'] ?? false));
                                $product->metadata = [
                                    'pancake_product_data' => $productPancakeDetails,
                                    'last_sync_product_level' => now()
                                ];
                                $product->save();
                                $stats['products_created']++;

                                // Create variant for new product
                                $newVariant = $product->variants()->create([
                                    'pancake_variant_id' => $variationPancakeData['id'],
                                    'name' => $variationPancakeData['name'] ?? $product->name,
                                    'sku' => $variationPancakeData['sku'] ?? $variationPancakeData['barcode'] ?? null,
                                    'price' => $variationPancakeData['retail_price'] ?? 0,
                                    'cost' => $variationPancakeData['last_imported_price'] ?? ($variationPancakeData['cost_price'] ?? 0),
                                    'stock' => $variationPancakeData['remain_quantity'] ?? ($variationPancakeData['stock'] ?? 0),
                                    'is_active' => !($variationPancakeData['is_hidden'] ?? ($variationPancakeData['is_removed'] ?? false)),
                                    'pancake_product_id' => $productPancakeId,
                                    'metadata' => [
                                        'pancake_variation_data' => $variationPancakeData,
                                        'last_sync_variation_level' => now()
                                    ]
                                ]);
                                $stats['variants_created']++;
                            } catch (\Exception $e) {
                                Log::error('Error creating new product and variant: ' . $e->getMessage(), [
                                    'pancake_id' => $productPancakeId,
                                    'pancake_variant_id' => $variationPancakeData['id']
                                ]);
                                $stats['errors']++;
                                continue;
                            }
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorMessage = 'Lỗi xử lý dữ liệu sản phẩm/biến thể từ Pancake tại trang ' . $page . ': ' . $e->getMessage();
                    Log::error($errorMessage, [
                        'page' => $page,
                        'pancake_shop_id' => $this->shopId,
                        'exception_type' => get_class($e),
                        'exception_trace' => $e->getTraceAsString()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'page_processed_before_error' => $page,
                        'error_details' => $e->getMessage()
                    ], 500);
                }

                if ($page >= $totalPages) {
                    break;
                }
                $page++;
            }

            // Deactivate products that were not in the sync
            try {
                $deactivatedCount = Product::whereNotIn('pancake_id', $processedPancakeIds)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                if ($deactivatedCount > 0) {
                    Log::info("Deactivated {$deactivatedCount} products that were not in the sync");
                }
            } catch (\Exception $e) {
                Log::error('Error deactivating old products: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Products and variants sync completed. Pages processed: %d. Products: %d created, %d updated. Variants: %d created, %d updated. Errors: %d.',
                    $stats['pages_processed'],
                    $stats['products_created'],
                    $stats['products_updated'],
                    $stats['variants_created'],
                    $stats['variants_updated'],
                    $stats['errors']
                ),
                'stats' => $stats
            ]);

        // } catch (\Exception $e) {
        //     Log::error('Error syncing products: ' . $e->getMessage());
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error syncing products: ' . $e->getMessage()
        //     ], 500);
        // }
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required',
                    'data' => []
                ]);
            }

            $products = Product::with(['variants'])
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('sku', 'like', '%' . $query . '%')
                      ->orWhereHas('variants', function($vq) use ($query) {
                          $vq->where('sku', 'like', '%' . $query . '%')
                            ->orWhere('name', 'like', '%' . $query . '%');
                      });
                })
                ->where('is_active', true)
                ->limit(10)
                ->get()
                ->map(function($product) {
                    $variant = $product->variants->first();
                    return [
                        'id' => $product->id,
                        'pancake_id' => $product->pancake_id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'image_url' => $product->metadata['image_url'] ?? null,
                        'price' => $variant ? $variant->price : 0,
                        'variation_info' => $variant ? [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'name' => $variant->name,
                            'price' => $variant->price,
                            'stock' => $variant->stock
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => count($products) > 0 ? 'Products found' : 'No products found',
                'data' => $products
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching products',
                'data' => []
            ], 500);
        }
    }
}
