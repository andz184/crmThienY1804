<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PancakeCategory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSyncController extends Controller
{
    protected $baseUri;
    protected $apiKey;
    protected $shopId;

    public function __construct()
    {
        $this->baseUri = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1/'), '/');
        $this->apiKey = config('pancake.api_key');
        $this->shopId = config('pancake.shop_id');

        $this->middleware('permission:products.sync');
    }

    /**
     * Sync products from Pancake to CRM
     */
    public function syncFromPancake(Request $request)
    {
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

            // Get products from Pancake API
            $response = Http::get("{$this->baseUri}/shops/{$this->shopId}/products", [
                'api_key' => $this->apiKey,
                'page_size' => 100,
                'page_number' => $request->input('page', 1)
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

            $data = $response->json();

            DB::beginTransaction();
            try {
                foreach ($data['data'] as $productData) {
                    // Process each product
                    $product = Product::updateOrCreate(
                        ['pancake_id' => $productData['id']],
                        [
                            'name' => $productData['name'],
                            'sku' => $productData['sku'] ?? null,
                            'description' => $productData['description'] ?? null,
                            'is_active' => !($productData['is_removed'] ?? false),
                        ]
                    );

                    // Process variants
                    if (!empty($productData['variations'])) {
                        foreach ($productData['variations'] as $variantData) {
                            $variant = ProductVariant::updateOrCreate(
                                ['pancake_variant_id' => $variantData['id']],
                                [
                                    'pancake_product_id' => $productData['id'],
                                    'name' => $variantData['name'] ?? $productData['name'],
                                    'sku' => $variantData['sku'] ?? null,
                                    'price' => $variantData['retail_price'] ?? 0,
                                    'cost' => $variantData['cost'] ?? 0,
                                    'stock' => $variantData['stock'] ?? 0,
                                    'category_ids' => $variantData['category_ids'] ?? [],
                                    'attributes' => $variantData['attributes'] ?? null,
                                    'metadata' => [
                                        'barcode' => $variantData['barcode'] ?? null,
                                        'weight' => $variantData['weight'] ?? 0,
                                        'last_sync' => now(),
                                    ]
                                ]
                            );

                            if ($variant->wasRecentlyCreated) {
                                $stats['created']++;
                            } else {
                                $stats['updated']++;
                            }
                        }
                    }
                }

                DB::commit();
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
                DB::rollBack();
                Log::error('Error syncing products', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error syncing products: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error in product sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error in product sync: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push product updates to Pancake
     */
    public function pushToPancake(Request $request, Product $product)
    {
        try {
            if (empty($this->apiKey) || empty($this->shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pancake API key or Shop ID is not configured.'
                ], 400);
            }

            // Prepare product data for Pancake
            $productData = [
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'is_removed' => !$product->is_active,
                'variations' => []
            ];

            // Add variants data
            foreach ($product->variants as $variant) {
                $productData['variations'][] = [
                    'id' => $variant->pancake_variant_id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'retail_price' => $variant->price,
                    'cost' => $variant->cost,
                    'stock' => $variant->stock,
                    'category_ids' => $variant->category_ids,
                    'attributes' => $variant->attributes,
                    'barcode' => $variant->metadata['barcode'] ?? null,
                    'weight' => $variant->metadata['weight'] ?? 0
                ];
            }

            // Send to Pancake API
            $response = Http::put("{$this->baseUri}/shops/{$this->shopId}/products/{$product->pancake_id}", [
                'api_key' => $this->apiKey,
                'product' => $productData
            ]);

            if (!$response->successful()) {
                Log::error('Failed to push product to Pancake', [
                    'product_id' => $product->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to push product to Pancake API'
                ], $response->status());
            }

            return response()->json([
                'success' => true,
                'message' => 'Product successfully pushed to Pancake',
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            Log::error('Error pushing product to Pancake', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error pushing product to Pancake: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product inventory in Pancake
     */
    public function updateInventory(Request $request, ProductVariant $variant)
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
                'stock' => 'required|integer|min:0',
                'warehouse_id' => 'required|exists:warehouses,id'
            ]);

            // Get warehouse
            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            // Prepare inventory data
            $inventoryData = [
                'variation_id' => $variant->pancake_variant_id,
                'warehouse_id' => $warehouse->pancake_id,
                'stock' => $request->stock
            ];

            // Send to Pancake API
            $response = Http::put("{$this->baseUri}/shops/{$this->shopId}/inventory", [
                'api_key' => $this->apiKey,
                'inventory' => $inventoryData
            ]);

            if (!$response->successful()) {
                Log::error('Failed to update inventory in Pancake', [
                    'variant_id' => $variant->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update inventory in Pancake API'
                ], $response->status());
            }

            // Update local inventory
            $variant->stock = $request->stock;
            $variant->save();

            return response()->json([
                'success' => true,
                'message' => 'Inventory successfully updated in Pancake',
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating inventory in Pancake', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating inventory in Pancake: ' . $e->getMessage()
            ], 500);
        }
    }
}
