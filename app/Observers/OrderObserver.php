<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\DailyRevenueAggregate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // For logging errors or info

class OrderObserver
{
    // Define the status that counts towards revenue
    // Ensure this matches the status used in your aggregation command and dashboard
    private $revenueStatus = Order::STATUS_DA_THU_TIEN;

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->updateCustomerFromOrder($order);
        $this->updateLiveSessionRevenue($order);
        $this->updateLiveSessionReport($order);

        // Dispatch the OrderCreated event for order assignment
        event(new \App\Events\OrderCreated($order));
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Only update customer if relevant fields like status or financials changed, or if customer details changed.
        // For simplicity, we can call it on every update, but be mindful of performance on high-traffic sites.
        // A more granular check could be: if ($order->isDirty('status') || $order->isDirty('customer_phone') || ...)
        $this->updateCustomerFromOrder($order);
        $this->updateLiveSessionRevenue($order);
        $this->updateLiveSessionReport($order);

        event(new \App\Events\OrderUpdated($order));
    }

    /**
     * Handle the Order "deleted" event.
     * (Optional: you might want to recalculate customer stats if orders can be hard deleted and it affects totals)
     */
    public function deleted(Order $order): void
    {
        // If an order is deleted, we might need to adjust customer aggregates.
        // This can be complex if orders contribute to total_spent, total_orders_count.
        // For now, we'll log it. A more robust solution might queue a job to recalculate.
        Log::info("Order [ID:{$order->id}] for customer phone [{$order->customer_phone}] deleted. Customer aggregates might need recalculation.");
        // $this->recalculateCustomerAggregates($order->customer_phone); // Implement this if needed
        $this->updateLiveSessionRevenue($order);
        $this->updateLiveSessionReport($order);
    }

    /**
     * Handle the Order "saved" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function saved(Order $order)
    {
        // Get original attributes for comparison, especially for updates
        $originalStatus = $order->getOriginal('status');
        $originalTotalValue = (float) $order->getOriginal('total_value', 0);
        $originalUserId = $order->getOriginal('user_id');
        $originalCreatedAtDate = $order->getOriginal('created_at') ? Carbon::parse($order->getOriginal('created_at'))->toDateString() : null;

        $currentStatus = $order->status;
        $currentTotalValue = (float) $order->total_value;
        $currentUserId = $order->user_id;
        $currentCreatedAtDate = Carbon::parse($order->created_at)->toDateString();

        // Scenario 1: Order existed and its relevant attributes for aggregation changed
        if ($order->wasRecentlyCreated === false) {
            // If status, user_id, created_at date, or total_value relevant to revenue changed
            $relevantChange = false;

            // Check if it was previously contributing to revenue
            if ($originalStatus === $this->revenueStatus && $originalUserId && $originalCreatedAtDate) {
                $this->updateAggregate($originalCreatedAtDate, $originalUserId, -$originalTotalValue, -1);
                $relevantChange = true;
            }

            // Check if it is now contributing to revenue
            if ($currentStatus === $this->revenueStatus && $currentUserId && $currentCreatedAtDate) {
                $this->updateAggregate($currentCreatedAtDate, $currentUserId, $currentTotalValue, 1);
                $relevantChange = true;
            }
            // If no change in status being revenue-contributing or not, but other details changed
            // This case is implicitly handled by the two blocks above for simplicity.
            // A more complex scenario would be if only total_value changed while status remained $this->revenueStatus.
            // The above logic (decrement old, increment new) covers this.

        }
        // Scenario 2: New order is created and is relevant for revenue
        else if ($order->wasRecentlyCreated === true && $currentStatus === $this->revenueStatus && $currentUserId && $currentCreatedAtDate) {
            $this->updateAggregate($currentCreatedAtDate, $currentUserId, $currentTotalValue, 1);
        }
    }

    /**
     * Helper function to update or create the daily revenue aggregate.
     */
    protected function updateAggregate(string $aggregationDate, int $userId, float $revenueChange, int $countChange)
    {
        if (empty($aggregationDate) || empty($userId)) {
            // Log or handle error: missing date or user_id
            logger()->warning('OrderObserver: Missing aggregation_date or user_id for aggregation.', [
                'date' => $aggregationDate,
                'user_id' => $userId
            ]);
            return;
        }

        // Use a transaction to ensure atomicity if multiple operations were needed
        // For simple increment/decrement, DB::transaction might be overkill but good practice for complex logic
        DB::transaction(function () use ($aggregationDate, $userId, $revenueChange, $countChange) {
            $aggregate = DailyRevenueAggregate::firstOrNew([
                'aggregation_date' => $aggregationDate,
                'user_id' => $userId,
            ]);

            $aggregate->total_revenue = ($aggregate->total_revenue ?? 0) + $revenueChange;
            $aggregate->completed_orders_count = ($aggregate->completed_orders_count ?? 0) + $countChange;

            // Ensure counts don't go negative if there are inconsistencies, though ideally they shouldn't
            if ($aggregate->completed_orders_count < 0) $aggregate->completed_orders_count = 0;
            if ($aggregate->total_revenue < 0) $aggregate->total_revenue = 0; // Assuming revenue cannot be negative

            if ($aggregate->exists || ($revenueChange != 0 || $countChange != 0)) {
                 // Only save if it exists or if there are actual changes to new record
                 // Or if it's a new record that should exist (e.g. count became 1)
                if ($aggregate->completed_orders_count > 0 || $aggregate->total_revenue > 0 || $aggregate->exists) {
                    $aggregate->save();
                } elseif ($aggregate->exists && $aggregate->completed_orders_count == 0 && $aggregate->total_revenue == 0) {
                    // Optional: Delete aggregate if it becomes all zeros
                    // $aggregate->delete();
                }
            }
        });
    }

    protected function updateCustomerFromOrder(Order $order): void
    {
        if (empty($order->customer_phone)) {
            Log::warning("Order [ID:{$order->id}] has no customer phone, cannot update customer.");
            return;
        }

        DB::transaction(function () use ($order) {
            // Find customer by phone number
            $customerPhone = CustomerPhone::where('phone_number', $order->customer_phone)->first();
            $customer = null;

            if ($customerPhone) {
                $customer = $customerPhone->customer;
            } else {
                // Create new customer and phone number
                $customerData = [
                    'name' => $order->customer_name,
                    'full_address' => $order->address_full,
                    'province' => $order->province_code,
                    'district' => $order->district_code,
                    'ward' => $order->ward_code,
                    'street_address' => $order->street_address,
                    'first_order_date' => $order->created_at->toDateString(),
                    'last_order_date' => $order->created_at->toDateString(),
                ];

                // Only add email if not empty
                if (!empty($order->customer_email)) {
                    $customerData['email'] = $order->customer_email;
                }

                $customer = Customer::create($customerData);

                CustomerPhone::create([
                    'customer_id' => $customer->id,
                    'phone_number' => $order->customer_phone,
                    'is_primary' => true,
                ]);
            }

            // Update customer information
            $customer->name = $order->customer_name ?: $customer->name;
            if (!empty($order->address_full)) $customer->full_address = $order->address_full;

            if ($order->province_code) {
                $customer->province = $order->province_code;
            } elseif ($order->province) {
                $customer->province = $order->province->code;
            } else {
                $customer->province = null;
            }

            if ($order->district_code) {
                $customer->district = $order->district_code;
            } elseif ($order->district) {
                $customer->district = $order->district->code;
            } else {
                $customer->district = null;
            }

            if ($order->ward_code) {
                $customer->ward = $order->ward_code;
            } elseif ($order->ward) {
                $customer->ward = $order->ward->code;
            } else {
                $customer->ward = null;
            }

            if (!empty($order->street_address)) $customer->street_address = $order->street_address;

            // Email: update if new order has it and we should update
            if (!empty($order->customer_email)) {
                // Check if new email is unique if it's being changed to something new
                if ($order->customer_email !== $customer->email) {
                    if (Customer::where('email', $order->customer_email)->where('id', '!=', $customer->id)->doesntExist()) {
                        $customer->email = $order->customer_email;
                    } else {
                        Log::warning("Attempted to update customer [ID: {$customer->id}] with email [{$order->customer_email}] that already exists for another customer.");
                    }
                }
            }

            $customer->last_order_date = $order->created_at->toDateString();
            $customer->save();
        });
    }

    /**
     * Optional: Implement full recalculation logic if needed, e.g., after order deletion.
     */
    // protected function recalculateCustomerAggregates(string $customerPhone): void
    // {
    //     $customer = Customer::where('phone', $customerPhone)->first();
    //     if ($customer) {
    //         $aggregates = Order::where('customer_phone', $customerPhone)
    //                            ->selectRaw('COUNT(*) as total_orders, SUM(total_value) as total_spent_value, MIN(created_at) as first_order, MAX(created_at) as last_order')
    //                            // ->whereIn('status', ['completed', 'shipped']) // Example
    //                            ->first();
    //         if ($aggregates) {
    //             $customer->total_orders_count = $aggregates->total_orders ?: 0;
    //             $customer->total_spent = $aggregates->total_spent_value ?: 0.00;
    //             $customer->first_order_date = $aggregates->first_order ? date('Y-m-d', strtotime($aggregates->first_order)) : null;
    //             $customer->last_order_date = $aggregates->last_order ? date('Y-m-d', strtotime($aggregates->last_order)) : null;
    //             $customer->save();
    //         } else {
    //             // No orders left, perhaps clear stats or set to 0/null
    //             $customer->total_orders_count = 0;
    //             $customer->total_spent = 0.00;
    //             // $customer->first_order_date = null; // Decide policy
    //             // $customer->last_order_date = null;
    //             $customer->save();
    //         }
    //     }
    // }

    private function updateLiveSessionRevenue($order)
    {
        try {

            // Check if order has live session info
            if (empty($order->live_session_info)) {
                \Illuminate\Support\Facades\Log::info('Order has no live session info', ['order_id' => $order->id]);
                return;
            }

            $liveSessionInfo = is_array($order->live_session_info)
                ? $order->live_session_info
                : json_decode($order->live_session_info, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Illuminate\Support\Facades\Log::error('Failed to decode live session info', [
                    'order_id' => $order->id,
                    'error' => json_last_error_msg(),
                    'live_session_info' => $order->live_session_info
                ]);
                return;
            }

            if (empty($liveSessionInfo['live_number']) || empty($liveSessionInfo['session_date'])) {
                \Illuminate\Support\Facades\Log::info('Live session info missing required fields', [
                    'order_id' => $order->id,
                    'live_session_info' => $liveSessionInfo
                ]);
                return;
            }

            $date = $liveSessionInfo['session_date'];
            $liveNumber = $liveSessionInfo['live_number'];

            \Illuminate\Support\Facades\Log::info('Processing live session revenue', [
                'order_id' => $order->id,
                'date' => $date,
                'live_number' => $liveNumber
            ]);

            // Get or create live session revenue record
            $revenue = \App\Models\LiveSessionRevenue::firstOrNew([
                'date' => $date,
                'live_number' => $liveNumber
            ]);

            // Set session name if not set
            if (!$revenue->session_name) {
                $revenue->session_name = "LIVE {$liveNumber} (" . \Carbon\Carbon::parse($date)->format('d/m/Y') . ")";
            }

            // Get all orders for this live session using JSON_EXTRACT
            $sessionOrders = \App\Models\Order::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(live_session_info, '$.session_date')) = ?", [$date])
                ->whereRaw("JSON_EXTRACT(live_session_info, '$.live_number') = ?", [$liveNumber])
                ->get();

            \Illuminate\Support\Facades\Log::info('Found session orders', [
                'order_id' => $order->id,
                'total_orders' => $sessionOrders->count()
            ]);
            $topProducts = [];
            foreach ($sessionOrders as $order) {
                // Only process completed orders for top products
                // if ($order->pancake_status !== \App\Models\Order::PANCAKE_STATUS_COMPLETED) {
                //     continue;
                // }
                $data = json_decode($order->products_data, true);
                // if (!is_array($data)) continue;
                // dd($data);

                foreach ($data as $item) {
                    $productName = $item['name'];
                    $productId = $item['variation_id'];
                    $productNameVariation = $item['variation_info']['name'];
                    $quantity = (int)$item['quantity'];
                    $price = (float)$item['variation_info']['retail_price'];
                    $totalAmount = $quantity * $price;

                    if (!isset($topProducts[$productId])) {
                        $topProducts[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'total_quantity' => 0,
                            'total_revenue' => 0,
                            'unit_price' => $price
                        ];
                    }

                    $topProducts[$productId]['total_quantity'] += $quantity;
                    $topProducts[$productId]['total_revenue'] += $totalAmount;

                }
            }
            dd($topProducts);
            // Sort products by revenue in descending order
            uasort($topProducts, function($a, $b) {
                return $b['total_revenue'] <=> $a['total_revenue'];
            });

            // Convert to array and store in revenue record
            $revenue->top_products = $topProducts;

            $revenue->total_orders = $sessionOrders->count();
            $revenue->successful_orders = $sessionOrders->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_COMPLETED)->count();
            $revenue->canceled_orders = $sessionOrders->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_CANCELED)->count();
            $revenue->delivering_orders = $sessionOrders->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_SHIPPING)->count();
            $revenue->total_revenue = $sessionOrders->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_COMPLETED)->sum('total_value');

            // Calculate customer statistics
            $customerIds = $sessionOrders->pluck('customer_id')->unique();
            $revenue->total_customers = $customerIds->count();

            $newCustomers = 0;
            foreach ($customerIds as $customerId) {
                $firstOrder = \App\Models\Order::where('customer_id', $customerId)
                    ->orderBy('created_at')
                    ->first();

                if ($firstOrder && $firstOrder->live_session_info) {
                    $firstOrderInfo = is_array($firstOrder->live_session_info)
                        ? $firstOrder->live_session_info
                        : json_decode($firstOrder->live_session_info, true);

                    if (isset($firstOrderInfo['session_date']) &&
                        $firstOrderInfo['session_date'] === $date &&
                        isset($firstOrderInfo['live_number']) &&
                        $firstOrderInfo['live_number'] === $liveNumber) {
                        $newCustomers++;
                    }
                }
            }
            $revenue->new_customers = $newCustomers;
            $revenue->returning_customers = $revenue->total_customers - $newCustomers;

            // Calculate rates
            if ($revenue->total_orders > 0) {
                $revenue->conversion_rate = ($revenue->successful_orders / $revenue->total_orders) * 100;
                $revenue->cancellation_rate = ($revenue->canceled_orders / $revenue->total_orders) * 100;
            }

            // Calculate orders by status
            $ordersByStatus = [];
            foreach ($sessionOrders as $order) {
                $status = $order->pancake_status;
                if (!isset($ordersByStatus[$status])) {
                    $ordersByStatus[$status] = [
                        'count' => 0,
                        'revenue' => 0
                    ];
                }
                $ordersByStatus[$status]['count']++;
                $ordersByStatus[$status]['revenue'] += $order->total_value;
            }
            $revenue->orders_by_status = $ordersByStatus;

            // Calculate orders by province
            $ordersByProvince = [];
            foreach ($sessionOrders as $order) {
                if ($order->province_code) {
                    if (!isset($ordersByProvince[$order->province_code])) {
                        $ordersByProvince[$order->province_code] = [
                            'count' => 0,
                            'revenue' => 0
                        ];
                    }
                    $ordersByProvince[$order->province_code]['count']++;
                    $ordersByProvince[$order->province_code]['revenue'] += $order->total_value;
                }
            }

            // Ensure orders_by_province is properly encoded as JSON string
            $revenue->orders_by_province = json_encode($ordersByProvince);


            \Illuminate\Support\Facades\Log::info('Saving live session revenue', [
                'order_id' => $order->id,
                'revenue_data' => $revenue->toArray()
            ]);

            $revenue->save();
            dd($revenue);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in updateLiveSessionRevenue', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function restored(Order $order)
    {
        $this->updateLiveSessionRevenue($order);
        $this->updateLiveSessionReport($order);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order)
    {
        $this->updateLiveSessionReport($order);
    }

    /**
     * Update live session report when order changes
     */
    private function updateLiveSessionReport(Order $order)
    {
        try {
            if (empty($order->live_session_info)) {
                return;
            }

            $liveSessionInfo = is_array($order->live_session_info)
                ? $order->live_session_info
                : json_decode($order->live_session_info, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Illuminate\Support\Facades\Log::error('Failed to decode live session info', [
                    'order_id' => $order->id,
                    'error' => json_last_error_msg(),
                    'live_session_info' => $order->live_session_info
                ]);
                return;
            }

            if (empty($liveSessionInfo['session_date'])) {
                return;
            }

            // Recalculate report for the order's date
            \App\Models\LiveSessionReport::calculateAndStore($liveSessionInfo['session_date'], 'daily');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating live session report', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
