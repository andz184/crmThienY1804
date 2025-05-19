<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Create a new OrderController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get a list of orders with optional filters
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['items', 'warehouse']);

            // Apply filters
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            if ($request->seller_id) {
                $query->where('user_id', $request->seller_id);
            }
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('order_code', 'like', "%{$request->search}%")
                      ->orWhere('customer_name', 'like', "%{$request->search}%")
                      ->orWhere('customer_phone', 'like', "%{$request->search}%");
                });
            }

            // Sort
            $sortField = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortField, $sortOrder);

            // Paginate
            $perPage = $request->per_page ?? 15;
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific order by ID
     */
    public function show($id)
    {
        try {
            $order = Order::with(['items', 'warehouse'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bill_phone_number' => 'required|string|max:20',
                'bill_full_name' => 'required|string|max:255',
                'assigning_seller_id' => 'nullable',
                'assigning_care_id' => 'nullable',
                'warehouse_id' => 'nullable',
                'shipping_fee' => 'nullable|numeric',
                'note' => 'nullable|string',
                'note_print' => 'nullable|string',
                'transfer_money' => 'nullable|numeric',
                'partner' => 'nullable|array',
                'partner.partner_id' => 'nullable|string',
                'partner.partner_name' => 'nullable|string',
                'shipping_address' => 'nullable|array',
                'shipping_address.address' => 'nullable|string',
                'shipping_address.commune_id' => 'nullable|string',
                'shipping_address.country_code' => 'nullable|string',
                'shipping_address.district_id' => 'nullable|string',
                'shipping_address.province_id' => 'nullable|string',
                'shipping_address.full_name' => 'nullable|string',
                'shipping_address.phone_number' => 'nullable|string',
                'shipping_address.post_code' => 'nullable|string',
                'order_sources' => 'nullable|integer',
                'page_id' => 'nullable|string',
                'account' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.variation_id' => 'nullable|string',
                'items.*.quantity' => 'nullable|integer',
                'items.*.variation_info' => 'nullable|array',
                'items.*.variation_info.retail_price' => 'nullable|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate subtotal from items if provided
            $subtotal = 0;
            if ($request->has('items')) {
                $subtotal = collect($request->items)->sum(function ($item) {
                    return ($item['quantity'] ?? 1) * ($item['variation_info']['retail_price'] ?? 0);
                });
            }

            // Calculate total
            $total = $subtotal;
            if ($request->shipping_fee) {
                $total += floatval($request->shipping_fee);
            }
            if ($request->transfer_money) {
                $total += floatval($request->transfer_money);
            }

            // Create order with all fields
            $order = Order::create([
                'order_code' => 'ORD-' . time() . rand(1000, 9999),
                'customer_name' => $request->bill_full_name,
                'customer_phone' => $request->bill_phone_number,
                'full_address' => $request->shipping_address['address'] ?? null,
                'province_code' => $request->shipping_address['province_id'] ?? null,
                'district_code' => $request->shipping_address['district_id'] ?? null,
                'ward_code' => $request->shipping_address['commune_id'] ?? null,
                'street_address' => $request->shipping_address['address'] ?? null,
                'notes' => $request->note ?? null,
                'additional_notes' => $request->note_print ?? null,
                'shipping_fee' => $request->shipping_fee ?? 0,
                'transfer_money' => $request->transfer_money ?? 0,
                'subtotal' => $subtotal,
                'total_value' => $total,
                'status' => Order::STATUS_MOI,
                'user_id' => $request->assigning_seller_id,
                'warehouse_id' => $request->warehouse_id,
                'pancake_page_id' => $request->page_id,
                'additional_data' => [
                    'assigning_care_id' => $request->assigning_care_id,
                    'partner' => $request->partner,
                    'third_party' => $request->third_party,
                    'order_sources' => $request->order_sources,
                    'account' => $request->account,
                    'shipping_address_full' => $request->shipping_address
                ]
            ]);

            // Add items if provided
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $order->items()->create([
                        'code' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['variation_info']['retail_price'] ?? 0,
                        'additional_data' => [
                            'variation_info' => $item['variation_info'] ?? null
                        ]
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->load('items')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing order
     */
    public function update(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'bill_phone_number' => 'nullable|string|max:20',
                'bill_full_name' => 'nullable|string|max:255',
                'assigning_seller_id' => 'nullable|uuid',
                'assigning_care_id' => 'nullable|uuid',
                'warehouse_id' => 'nullable|uuid',
                'shipping_fee' => 'nullable|numeric',
                'note' => 'nullable|string',
                'note_print' => 'nullable|string',
                'transfer_money' => 'nullable|numeric',
                'partner' => 'nullable|array',
                'partner.partner_id' => 'nullable|string',
                'partner.partner_name' => 'nullable|string',
                'shipping_address' => 'nullable|array',
                'shipping_address.address' => 'nullable|string',
                'shipping_address.commune_id' => 'nullable|string',
                'shipping_address.country_code' => 'nullable|string',
                'shipping_address.district_id' => 'nullable|string',
                'shipping_address.province_id' => 'nullable|string',
                'shipping_address.full_name' => 'nullable|string',
                'shipping_address.phone_number' => 'nullable|string',
                'shipping_address.post_code' => 'nullable|string',
                'order_sources' => 'nullable|integer',
                'page_id' => 'nullable|string',
                'account' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.variation_id' => 'nullable|string',
                'items.*.quantity' => 'nullable|integer',
                'items.*.variation_info' => 'nullable|array',
                'items.*.variation_info.retail_price' => 'nullable|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update basic order information
            $updateData = [];

            if ($request->has('bill_full_name')) {
                $updateData['customer_name'] = $request->bill_full_name;
            }
            if ($request->has('bill_phone_number')) {
                $updateData['customer_phone'] = $request->bill_phone_number;
            }
            if ($request->has('warehouse_id')) {
                $updateData['warehouse_id'] = $request->warehouse_id;
            }
            if ($request->has('shipping_fee')) {
                $updateData['shipping_fee'] = $request->shipping_fee;
            }
            if ($request->has('note')) {
                $updateData['notes'] = $request->note;
            }
            if ($request->has('note_print')) {
                $updateData['additional_notes'] = $request->note_print;
            }
            if ($request->has('transfer_money')) {
                $updateData['transfer_money'] = $request->transfer_money;
            }
            if ($request->has('assigning_seller_id')) {
                $updateData['user_id'] = $request->assigning_seller_id;
            }
            if ($request->has('page_id')) {
                $updateData['pancake_page_id'] = $request->page_id;
            }

            // Update shipping address if provided
            if ($request->has('shipping_address')) {
                $updateData['full_address'] = $request->shipping_address['address'] ?? null;
                $updateData['province_code'] = $request->shipping_address['province_id'] ?? null;
                $updateData['district_code'] = $request->shipping_address['district_id'] ?? null;
                $updateData['ward_code'] = $request->shipping_address['commune_id'] ?? null;
                $updateData['street_address'] = $request->shipping_address['address'] ?? null;
            }

            // Update additional data
            $additionalData = $order->additional_data ?? [];
            if ($request->has('assigning_care_id')) {
                $additionalData['assigning_care_id'] = $request->assigning_care_id;
            }
            if ($request->has('partner')) {
                $additionalData['partner'] = $request->partner;
            }
            if ($request->has('third_party')) {
                $additionalData['third_party'] = $request->third_party;
            }
            if ($request->has('order_sources')) {
                $additionalData['order_sources'] = $request->order_sources;
            }
            if ($request->has('account')) {
                $additionalData['account'] = $request->account;
            }
            if ($request->has('shipping_address')) {
                $additionalData['shipping_address_full'] = $request->shipping_address;
            }
            $updateData['additional_data'] = $additionalData;

            // Update items if provided
            if ($request->has('items')) {
                // Remove existing items
                $order->items()->delete();

                // Add new items
                foreach ($request->items as $item) {
                    $order->items()->create([
                        'code' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['variation_info']['retail_price'] ?? 0,
                        'additional_data' => [
                            'variation_info' => $item['variation_info'] ?? null
                        ]
                    ]);
                }

                // Recalculate totals
                $subtotal = collect($request->items)->sum(function ($item) {
                    return ($item['quantity'] ?? 1) * ($item['variation_info']['retail_price'] ?? 0);
                });

                $updateData['subtotal'] = $subtotal;
                $updateData['total_value'] = $subtotal + floatval($request->shipping_fee ?? $order->shipping_fee) + floatval($request->transfer_money ?? $order->transfer_money);
            }

            $order->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order->load('items')
            ]);

        } catch (\Exception $e) {
            Log::error('Order update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an order
     */
    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);

            // Delete related items first
            $order->items()->delete();

            // Delete the order
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Order deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
