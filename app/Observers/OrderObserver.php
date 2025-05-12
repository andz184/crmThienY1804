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
                $customer = Customer::create([
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'full_address' => $order->address_full,
                    'province' => $order->province_code,
                    'district' => $order->district_code,
                    'ward' => $order->ward_code,
                    'street_address' => $order->street_address,
                    'first_order_date' => $order->created_at->toDateString(),
                    'last_order_date' => $order->created_at->toDateString(),
                ]);

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

            // Email: update if new order has it and customer doesn't, or if it changed
            if (!empty($order->customer_email) && $order->customer_email !== $customer->email) {
                // Check if new email is unique if it's being changed to something new
                if (Customer::where('email', $order->customer_email)->where('id', '!=', $customer->id)->doesntExist()) {
                    $customer->email = $order->customer_email;
                } else {
                    Log::warning("Attempted to update customer [ID: {$customer->id}] with email [{$order->customer_email}] that already exists for another customer.");
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
                                   // ->whereIn('status', ['completed', 'shipped']) // Example
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
}
