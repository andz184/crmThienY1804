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

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email|max:255',
                'full_address' => 'nullable|string|max:500',
                'province' => 'nullable|string|max:100',
                'district' => 'nullable|string|max:100',
                'ward' => 'nullable|string|max:100',
                'street_address' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'source' => 'nullable|string|max:50',
                'items' => 'nullable|array',
                'items.*.product_name' => 'required_with:items|string|max:255',
                'items.*.quantity' => 'required_with:items|integer|min:1',
                'items.*.price' => 'required_with:items|numeric|min:0',
                'items.*.variant' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Tính tổng giá trị đơn hàng
            $total = 0;
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $total += $item['quantity'] * $item['price'];
                }
            }

            // Tạo đơn hàng
            $order = Order::create([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'full_address' => $request->full_address,
                'province' => $request->province,
                'district' => $request->district,
                'ward' => $request->ward,
                'street_address' => $request->street_address,
                'notes' => $request->notes,
                'source' => $request->source ?? 'api',
                'status' => 'pending',
                'total_value' => $total,
                'order_code' => 'ORD-' . time() . rand(1000, 9999),
                'user_id' => Auth::id()
            ]);

            // Thêm các sản phẩm vào đơn hàng nếu có
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $order->items()->create([
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'variant' => $item['variant'] ?? null,
                        'total' => $item['quantity'] * $item['price']
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'total_value' => $order->total_value,
                    'status' => $order->status
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating order via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
