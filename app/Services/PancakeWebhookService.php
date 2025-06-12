<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\ShippingProvider;
use App\Models\Warehouse;
use App\Models\LiveSessionRevenue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PancakeWebhookService
{
    /**
     * Process the incoming webhook data.
     *
     * @param array $webhookData
     * @return void
     * @throws \Exception
     */
    public function processWebhook(array $webhookData)
    {
        // Validate required fields
        if (empty($webhookData['id'])) {
            throw new \Exception('Missing required field: id');
        }

        // Format data
        $formattedData = $this->formatWebhookData($webhookData);

        Log::info('Formatted webhook data for job', [
            'order_id' => $formattedData['id'],
            'has_items' => !empty($formattedData['items']),
            'items_count' => !empty($formattedData['items']) ? count($formattedData['items']) : 0,
        ]);

        // Check if order exists
        $existingOrder = Order::where('pancake_order_id', $webhookData['id'])->first();

        if ($existingOrder) {
            Log::info('Updating existing order in job', ['order_id' => $existingOrder->id, 'pancake_order_id' => $existingOrder->pancake_order_id]);
            $this->updateOrderFromPancake($existingOrder, $formattedData);
        } else {
            Log::info('Creating new order in job', ['pancake_order_id' => $webhookData['id']]);
            $this->createOrderFromPancake($formattedData);
        }
    }

    /**
     * Format webhook data to match our internal structure.
     */
    private function formatWebhookData(array $webhookData): array
    {
        // Validate required fields
        if (empty($webhookData['id'])) {
            throw new \Exception('Missing required field: id in webhook data');
        }

        // Format data
        $formattedData = [
            'id' => $webhookData['id'],
            'code' => $webhookData['id'],
            'status' => $webhookData['status'] ?? 0,
            'inserted_at' => $webhookData['inserted_at'] ?? null,
            'status_name' => $webhookData['status_name'] ?? '',
            'order_sources' => $webhookData['order_sources'] ?? '-1',
            'order_sources_name' => $webhookData['order_sources_name'] ?? '',
            'warehouse_id' => $webhookData['warehouse_id'] ?? null,
            'page_id' => $webhookData['page_id'] ?? null,
            'total_price' => $webhookData['total_price'] ?? 0,
            'shipping_fee' => $webhookData['shipping_fee'] ?? 0,
            'total_quantity' => $webhookData['total_quantity'] ?? 0,
            'note' => $webhookData['note'] ?? '',
            'transfer_money' => $webhookData['transfer_money'] ?? 0,
            'prepaid' => $webhookData['prepaid'] ?? 0,
            'cod' => $webhookData['cod'] ?? 0,
            'customer' => [
                'id' => $webhookData['customer']['id'] ?? null,
                'name' => $webhookData['customer']['name'] ?? $webhookData['bill_full_name'] ?? '',
                'phone' => $webhookData['customer']['phone_numbers'][0] ?? $webhookData['bill_phone_number'] ?? null,
                'email' => $webhookData['customer']['emails'][0] ?? null,
                'gender' => $webhookData['customer']['gender'] ?? null,
                'fb_id' => $webhookData['customer']['fb_id'] ?? null
            ],
            'shipping_address' => [
                'full_name' => $webhookData['shipping_address']['full_name'] ?? $webhookData['bill_full_name'] ?? '',
                'phone_number' => $webhookData['shipping_address']['phone_number'] ?? $webhookData['bill_phone_number'] ?? '',
                'address' => $webhookData['shipping_address']['address'] ?? '',
                'province_id' => $webhookData['shipping_address']['province_id'] ?? null,
                'district_id' => $webhookData['shipping_address']['district_id'] ?? null,
                'commune_id' => $webhookData['shipping_address']['commune_id'] ?? null,
                'full_address' => $webhookData['shipping_address']['full_address'] ?? ''
            ],
            'assigning_seller' => [
                'id' => $webhookData['assigning_seller']['id'] ?? null,
                'email' => $webhookData['assigning_seller']['email'] ?? null,
                'fb_id' => $webhookData['assigning_seller']['fb_id'] ?? null,
                'name' => $webhookData['assigning_seller']['name'] ?? null,
                'phone_number' => $webhookData['assigning_seller']['phone_number'] ?? null,
                'avatar_url' => $webhookData['assigning_seller']['avatar_url'] ?? null
            ],
            'assigning_care' => [
                'id' => $webhookData['assigning_care']['id'] ?? null,
                'email' => $webhookData['assigning_care']['email'] ?? null,
                'fb_id' => $webhookData['assigning_care']['fb_id'] ?? null,
                'name' => $webhookData['assigning_care']['name'] ?? null,
                'phone_number' => $webhookData['assigning_care']['phone_number'] ?? null,
                'avatar_url' => $webhookData['assigning_care']['avatar_url'] ?? null
            ]
        ];

        if (!empty($webhookData['items'])) {
            $formattedData['items'] = $webhookData['items'];
        }

        if (!empty($webhookData['partner']) && is_array($webhookData['partner'])) {
            $formattedData['partner'] = [
                'partner_id' => $webhookData['partner']['partner_id'] ?? null,
                'partner_name' => $webhookData['partner']['partner_name'] ?? null
            ];
        }

        if (!empty($webhookData['warehouse_info'])) {
            $formattedData['warehouse_info'] = $webhookData['warehouse_info'];
        }
        if (!empty($webhookData['warehouse_name'])) {
            $formattedData['warehouse_name'] = $webhookData['warehouse_name'];
        }


        return $formattedData;
    }

    /**
     * Create a new order from Pancake data
     */
    protected function createOrderFromPancake(array $orderData)
    {
        DB::transaction(function () use ($orderData) {
            $customer = $this->findOrCreateCustomer([
                'name' => $orderData['customer']['name'] ?? null,
                'phone' => $orderData['customer']['phone'] ?? null,
                'email' => $orderData['customer']['email'] ?? null,
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'id' => $orderData['customer']['id'] ?? null,
                'code' => $orderData['customer']['code'] ?? null
            ]);

            $order = new Order();

            $order->pancake_order_id = $orderData['id'];
            $order->order_code = $orderData['code'] ?? ('PCK-' . Str::random(8));
            $order->source = $orderData['order_sources'] ?? -1;

            if (!empty($orderData['warehouse_id'])) {
                $warehouse = Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();

                if (!$warehouse) {
                    $warehouse = Warehouse::where('code', $orderData['warehouse_id'])->first();
                }

                if (!$warehouse && !empty($orderData['warehouse_name'])) {
                    $warehouse = new Warehouse();
                    $warehouse->name = $orderData['warehouse_name'];
                    $warehouse->code = 'WH-' . $orderData['warehouse_id'];
                    $warehouse->pancake_id = $orderData['warehouse_id'];
                    $warehouse->save();
                }

                if ($warehouse) {
                    $order->warehouse_id = $warehouse->id;
                    $order->warehouse_code = $warehouse->code;
                    $order->pancake_warehouse_id = $warehouse->pancake_id;
                } else {
                    $order->pancake_warehouse_id = $orderData['warehouse_id'];
                }
            }
            $order->pancake_page_id = $orderData['page_id'] ?? null;

            $order->customer_id = $customer->id;
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->phone;
            $order->customer_email = $customer->email;

            if (!empty($orderData['shipping_address'])) {
                $shipping = $orderData['shipping_address'];
                $addressParts = [];
                if (!empty($shipping['address'])) $addressParts[] = $shipping['address'];
                if (!empty($shipping['ward_name'])) $addressParts[] = $shipping['ward_name'];
                if (!empty($shipping['district_name'])) $addressParts[] = $shipping['district_name'];
                if (!empty($shipping['province_name'])) $addressParts[] = $shipping['province_name'];
                $fullAddress = implode(', ', $addressParts);
                $order->full_address = !empty($fullAddress) ? $fullAddress : ($shipping['full_address'] ?? '');
                $order->province_code = $shipping['province_id'] ?? null;
                $order->district_code = $shipping['district_id'] ?? null;
                $order->ward_code = $shipping['ward_id'] ?? null;
                $order->street_address = $shipping['address'] ?? '';
                $order->province_name = $shipping['province_name'] ?? null;
                $order->district_name = $shipping['district_name'] ?? null;
                $order->ward_name = $shipping['ward_name'] ?? null;
            }

            if (!empty($orderData['partner']['partner_id'])) {
                $providerId = $orderData['partner']['partner_id'];
                $provider = ShippingProvider::where('pancake_id', $providerId)
                    ->orWhere('pancake_partner_id', $providerId)
                    ->first();
                if ($provider) {
                    $order->shipping_provider_id = $provider->id;
                    $order->pancake_shipping_provider_id = $provider->pancake_id;
                } else {
                    $order->pancake_shipping_provider_id = $providerId;
                }
            }

            $order->pancake_inserted_at = !empty($orderData['inserted_at']) ? Carbon::parse($orderData['inserted_at'])->addHours(7) : null;
            $order->shipping_fee = (float)($orderData['shipping_fee'] ?? 0);
            $order->transfer_money = (float)($orderData['transfer_money'] ?? 0);
            $order->total_value = $this->calculateOrderTotal($orderData);
            $order->notes = $orderData['note'] ?? null;
            $order->additional_notes = $orderData['additional_notes'] ?? null;
            $order->status = $this->mapPancakeStatus($orderData['status'] ?? 'new');
            $order->pancake_status = $orderData['status'] ?? 0;

            if (!empty($orderData['items'])) {
                $order->products_data = json_encode($orderData['items']);
            }

            $order->assigning_seller_id = $orderData['assigning_seller']['id'] ?? null;
            $order->assigning_care_id = $orderData['assigning_care']['id'] ?? null;
            $order->save();

            if (!empty($orderData['note'])) {
                $liveSessionInfo = $this->parseLiveSessionInfo($orderData['note']);
                if ($liveSessionInfo) {
                    $order->live_session_info = json_encode($liveSessionInfo);
                    $order->save();
                    if (isset($liveSessionInfo['live_number']) && isset($liveSessionInfo['session_date'])) {
                         DB::transaction(function () use ($order, $liveSessionInfo) {
                            \App\Models\LiveSessionOrder::updateOrCreate(
                                ['order_id' => $order->id],
                                [
                                    'live_session_id' => "LIVE{$liveSessionInfo['live_number']}",
                                    'live_session_date' => Carbon::parse($liveSessionInfo['session_date'], 'UTC'),
                                    'customer_id' => $order->customer_id,
                                    'customer_name' => $order->customer_name,
                                    'shipping_address' => $order->full_address ?: $order->street_address,
                                    'total_amount' => $order->total_value
                                ]
                            );
                            LiveSessionRevenue::recalculateStats($liveSessionInfo['session_date'], $liveSessionInfo['live_number']);
                        });
                    }
                }
            }

            if ($customer) {
                $customer->total_orders_count = Order::where('customer_id', $customer->id)->count();
                $customer->total_spent = Order::where('customer_id', $customer->id)->where('pancake_status', 3)->sum('total_value');
                $customer->succeeded_order_count = Order::where('customer_id', $customer->id)->where('pancake_status', 3)->count();
                $customer->returned_order_count = Order::where('customer_id', $customer->id)->whereIn('pancake_status', [4, 5, 15])->count();
                $customer->save();
            }
        });
    }

    /**
     * Update existing order with Pancake data
     */
    protected function updateOrderFromPancake(Order $order, array $orderData)
    {
        DB::transaction(function () use ($order, $orderData) {
            if (!empty($orderData['customer'])) {
                $customer = $this->findOrCreateCustomer($orderData['customer']);
                $order->customer_id = $customer->id;
                $order->customer_name = $orderData['customer']['name'] ?? $customer->name;
                $order->customer_phone = $orderData['customer']['phone'] ?? $customer->phone;
                $order->customer_email = $orderData['customer']['email'] ?? $customer->email;
            }

            if (!empty($orderData['order_sources'])) {
                $order->source = $orderData['order_sources'];
            }

            if (!empty($orderData['warehouse_id'])) {
                $warehouse = Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();
                if (!$warehouse) {
                    $warehouse = Warehouse::where('code', $orderData['warehouse_id'])->first();
                }
                if (!$warehouse && !empty($orderData['warehouse_name'])) {
                    $warehouse = new Warehouse();
                    $warehouse->name = $orderData['warehouse_name'];
                    $warehouse->code = 'WH-' . $orderData['warehouse_id'];
                    $warehouse->pancake_id = $orderData['warehouse_id'];
                    $warehouse->save();
                }
                if ($warehouse) {
                    $order->warehouse_id = $warehouse->id;
                    $order->warehouse_code = $warehouse->code;
                    $order->pancake_warehouse_id = $warehouse->pancake_id;
                } else {
                    $order->pancake_warehouse_id = $orderData['warehouse_id'];
                }
            }

            $order->campaign_id = $orderData['campaign_id'] ?? $order->campaign_id;
            $order->campaign_name = $orderData['campaign_name'] ?? $order->campaign_name;
            if (!empty($orderData['inserted_at'])) {
                $order->pancake_inserted_at = Carbon::parse($orderData['inserted_at'])->addHours(7);
            }

            if (!empty($orderData['status'])) {
                $order->status = $this->mapPancakeStatus($orderData['status']);
                $order->pancake_status = is_numeric($orderData['status']) ?
                    $orderData['status'] :
                    ($orderData['status_name'] ?? $order->pancake_status);
            }

            if (!empty($orderData['shipping_address'])) {
                $shipping = $orderData['shipping_address'];
                $addressParts = [];
                if (!empty($shipping['address'])) $addressParts[] = $shipping['address'];
                if (!empty($shipping['ward_name'])) $addressParts[] = $shipping['ward_name'];
                if (!empty($shipping['district_name'])) $addressParts[] = $shipping['district_name'];
                if (!empty($shipping['province_name'])) $addressParts[] = $shipping['province_name'];
                $fullAddress = implode(', ', $addressParts);
                $order->full_address = !empty($fullAddress) ? $fullAddress : ($shipping['full_address'] ?? $order->full_address);
                $order->province_code = $shipping['province_id'] ?? $order->province_code;
                $order->district_code = $shipping['district_id'] ?? $order->district_code;
                $order->ward_code = $shipping['ward_id'] ?? $order->ward_code;
                $order->street_address = $shipping['address'] ?? $order->street_address;
                $order->province_name = $shipping['province_name'] ?? $order->province_name;
                $order->district_name = $shipping['district_name'] ?? $order->district_name;
                $order->ward_name = $shipping['ward_name'] ?? $order->ward_name;
            }

            if (!empty($orderData['partner']['partner_id'])) {
                $providerId = $orderData['partner']['partner_id'];
                $provider = ShippingProvider::where('pancake_id', $providerId)
                    ->orWhere('pancake_partner_id', $providerId)
                    ->first();
                if ($provider) {
                    $order->shipping_provider_id = $provider->id;
                    $order->pancake_shipping_provider_id = $provider->pancake_id;
                } else {
                    $order->pancake_shipping_provider_id = $providerId;
                }
            }

            $order->shipping_fee = $orderData['shipping_fee'] ?? $order->shipping_fee;
            $order->transfer_money = $orderData['transfer_money'] ?? $order->transfer_money;
            $order->total_value = $orderData['total_price'] ?? $order->total_value;
            $order->notes = $orderData['note'] ?? $order->notes;

            if (!empty($orderData['items'])) {
                $order->products_data = json_encode($orderData['items']);
            }

            $order->assigning_seller_id = $orderData['assigning_seller']['id'] ?? $order->assigning_seller_id;
            $order->assigning_care_id = $orderData['assigning_care']['id'] ?? $order->assigning_care_id;
            $order->save();

            if (!empty($orderData['note'])) {
                $liveSessionInfo = $this->parseLiveSessionInfo($orderData['note']);
                if ($liveSessionInfo) {
                    $order->live_session_info = json_encode($liveSessionInfo);
                    $order->save();
                    if (isset($liveSessionInfo['live_number']) && isset($liveSessionInfo['session_date'])) {
                        DB::transaction(function () use ($order, $liveSessionInfo){
                            \App\Models\LiveSessionOrder::updateOrCreate(
                                ['order_id' => $order->id],
                                [
                                    'live_session_id' => "LIVE{$liveSessionInfo['live_number']}",
                                    'live_session_date' => Carbon::parse($liveSessionInfo['session_date'], 'UTC'),
                                    'customer_id' => $order->customer_id,
                                    'customer_name' => $order->customer_name,
                                    'shipping_address' => $order->full_address ?: $order->street_address,
                                    'total_amount' => $order->total_value
                                ]
                            );
                            LiveSessionRevenue::recalculateStats($liveSessionInfo['session_date'], $liveSessionInfo['live_number']);
                        });
                    }
                }
            }

            if ($order->customer_id) {
                $customer = \App\Models\Customer::find($order->customer_id);
                if ($customer) {
                    $customer->total_orders_count = Order::where('customer_id', $customer->id)->count();
                    $customer->total_spent = Order::where('customer_id', $customer->id)->where('pancake_status', 3)->sum('total_value');
                    $customer->succeeded_order_count = Order::where('customer_id', $customer->id)->where('pancake_status', 3)->count();
                    $customer->returned_order_count = Order::where('customer_id', $customer->id)->whereIn('pancake_status', [4, 5, 15])->count();
                    $customer->save();
                }
            }
        });
    }

    private function parseLiveSessionInfo(?string $notes): ?array
    {
        if (empty($notes)) {
            return null;
        }
        $pattern = '/LIVE\s*(\d+)\s*(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i';
        if (preg_match($pattern, $notes, $matches)) {
            $liveNumber = $matches[1];
            $day = $matches[2];
            $month = $matches[3];
            $year = isset($matches[4]) ? $matches[4] : null;

            if (!$year) {
                $year = date('Y');
            } elseif (strlen($year) == 2) {
                $year = '20' . $year;
            }

            if (checkdate($month, $day, (int)$year)) {
                return [
                    'live_number' => $liveNumber,
                    'session_date' => sprintf('%s-%02d-%02d', $year, $month, $day),
                    'original_text' => trim($matches[0])
                ];
            }
        }
        return null;
    }

    private function findOrCreateCustomer(array $customerData)
    {
        if (empty($customerData)) {
            return new Customer();
        }

        $phone = $customerData['phone'] ?? $customerData['bill_phone_number'] ?? null;
        $name = $customerData['name'] ?? $customerData['bill_full_name'] ?? null;
        $email = $customerData['email'] ?? $customerData['bill_email'] ?? null;

        $customer = null;
        if (!empty($phone)) {
            $customer = Customer::where('phone', $phone)->first();
        }
        if (!$customer && !empty($email)) {
            $customer = Customer::where('email', $email)->first();
        }

        if (!$customer) {
            $customer = new Customer();
            $customer->name = $name ?? 'Unknown';
            $customer->phone = $phone;
            $customer->email = $email;
            $customer->pancake_id = $customerData['id'] ?? null;
            $customer->pancake_customer_id = $customerData['code'] ?? null;

            if (!empty($customerData['shipping_address'])) {
                $shipping = $customerData['shipping_address'];
                $customer->province = $shipping['province_id'] ?? null;
                $customer->district = $shipping['district_id'] ?? null;
                $customer->ward = $shipping['commune_id'] ?? null;
                $customer->street_address = $shipping['address'] ?? null;
                $customer->full_address = $shipping['full_address'] ?? null;
            }
            $customer->save();
        } else {
            $customer->pancake_id = $customerData['id'] ?? $customer->pancake_id;
            $customer->pancake_customer_id = $customerData['code'] ?? $customer->pancake_customer_id;
            if (!empty($customerData['shipping_address'])) {
                $shipping = $customerData['shipping_address'];
                $customer->province = $shipping['province_id'] ?? $customer->province;
                $customer->district = $shipping['district_id'] ?? $customer->district;
                $customer->ward = $shipping['commune_id'] ?? $customer->ward;
                $customer->street_address = $shipping['address'] ?? $customer->street_address;
                $customer->full_address = $shipping['full_address'] ?? $customer->full_address;
            }
            $customer->save();
        }
        return $customer;
    }

    private function calculateOrderTotal(array $orderData): float
    {
        if (isset($orderData['total_price'])) {
            return (float)$orderData['total_price'];
        }

        $total = 0;
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $price = $item['variation_info']['retail_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $total += $price * $quantity;
            }
        }
        return $total;
    }

    private function mapPancakeStatus($pancakeStatus): string
    {
        if (is_numeric($pancakeStatus)) {
            return match ((int)$pancakeStatus) {
                0 => 'moi',
                1 => 'dang_xu_ly',
                2 => 'dang_giao_hang',
                3 => 'hoan_thanh',
                4 => 'huy',
                5 => 'tra_hang',
                6 => 'cho_lay_hang',
                7 => 'da_lay_hang',
                8 => 'dang_giao',
                9 => 'da_giao',
                10 => 'khong_lay_duoc_hang',
                11 => 'cho_xac_nhan',
                12 => 'chuyen_hoan',
                13 => 'da_chuyen_hoan',
                default => 'moi',
            };
        }

        return match (strtolower((string)$pancakeStatus)) {
            'new' => 'moi',
            'processing' => 'dang_xu_ly',
            'shipping' => 'dang_giao_hang',
            'completed' => 'hoan_thanh',
            'cancelled' => 'huy',
            'returned' => 'tra_hang',
            'waiting_pickup' => 'cho_lay_hang',
            'picked_up' => 'da_lay_hang',
            'delivering' => 'dang_giao',
            'delivered' => 'da_giao',
            'pickup_failed' => 'khong_lay_duoc_hang',
            'waiting' => 'cho_xac_nhan',
            'returning' => 'chuyen_hoan',
            'returned_to_seller' => 'da_chuyen_hoan',
            default => 'moi',
        };
    }
}
