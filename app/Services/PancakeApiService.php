<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PancakeApiService
{
    protected $apiKey;
    protected $baseUrl;
    // No longer need baseUrlV2 if all calls go to v1
    // protected string $baseUrlV2;

    public function __construct()
    {
        $this->apiKey = config('pancake.api_key');
        $this->baseUrl = 'https://pos.pages.fm/api/v1';
        // $this->baseUrlV2 = 'https://pos.pages.fm/api/v1'; // Changed as per user request
    }

    /**
     * Build full URL for Pancake API endpoint
     */
    protected function buildUrl($endpoint)
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Create a new customer on Pancake.
     *
     * @param array $data ['name', 'phoneNumber', 'createType', 'emails' (optional array)]
     * @return array ['success' => bool, 'data' => customer_object|null, 'message' => string]
     */
    public function createCustomer(array $data): array
    {
        if (!$this->apiKey) {
            Log::error('Pancake API key not configured.');
            return ['success' => false, 'data' => null, 'message' => 'Pancake API key not configured.'];
        }

        $endpoint = $this->baseUrl . '/customers';

        $payload = [
            'name' => $data['name'],
            'phoneNumber' => $data['phoneNumber'],
            'createType' => $data['createType'] ?? 'ignore',
        ];

        if (!empty($data['emails']) && is_array($data['emails'])) {
            $payload['emails'] = $data['emails'];
        }

        Log::info('Pancake API: Attempting to create customer.', ['endpoint' => $endpoint, 'payload' => $payload]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($endpoint . '?api_key=' . $this->apiKey, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Pancake API: Create customer response.', ['status' => $response->status(), 'response_data' => $responseData]);

                if (isset($responseData['success']) && $responseData['success'] && !empty($responseData['data']) && isset($responseData['data']['id'])) {
                    return ['success' => true, 'data' => $responseData['data'], 'message' => 'Customer created/retrieved successfully on Pancake.'];
                } elseif (isset($responseData['success']) && $responseData['success']) {
                    Log::info('Pancake API: Create customer reported success but no specific customer data returned (possibly due to \'ignore\' and existing customer).', ['response_data' => $responseData]);
                    return ['success' => true, 'data' => null, 'message' => 'Pancake: Customer creation/ignore successful, but no specific data returned.'];
                }
                $errorMessage = $responseData['message'] ?? (is_string($responseData) ? $responseData : 'Pancake API: Create customer failed or unexpected response structure.');
                if (isset($responseData['error']) && is_string($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                } else if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                    $errorMessage = implode('; ', array_map(function($err){ return $err['message'] ?? 'Unknown error'; }, $responseData['errors']));
                }

                Log::warning('Pancake API: Create customer failed or unexpected response structure.', ['response_data' => $responseData]);
                return ['success' => false, 'data' => null, 'message' => $errorMessage];

            } else {
                $errorBody = $response->json() ?? $response->body();
                $errorMessage = 'Pancake API: Create customer HTTP request failed.';
                if(is_array($errorBody) && isset($errorBody['message'])) {
                    $errorMessage = $errorBody['message'];
                } else if (is_array($errorBody) && isset($errorBody['error'])) {
                    $errorMessage = $errorBody['error'];
                } else if (is_string($errorBody) && !empty($errorBody)) {
                    $errorMessage = $errorBody;
                }
                Log::error('Pancake API: Create customer HTTP request failed.', [
                    'status' => $response->status(),
                    'response_body' => $errorBody
                ]);
                return ['success' => false, 'data' => null, 'message' => $errorMessage, 'status_code' => $response->status()];
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Pancake API: RequestException during customer creation: ' . $e->getMessage(), ['payload' => $payload]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: RequestException - ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Pancake API: General Exception during customer creation: ' . $e->getMessage(), ['payload' => $payload]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: Exception - ' . $e->getMessage()];
        }
    }

    /**
     * Get customer details from Pancake by phone number.
     * Now uses baseUrlV1.
     *
     * @param string $phoneNumber
     * @return array ['success' => bool, 'data' => customer_object|null, 'message' => string]
     */
    public function getCustomerByPhone(string $phoneNumber): array
    {
        if (!$this->apiKey) {
            Log::error('Pancake API key not configured.');
            return ['success' => false, 'data' => null, 'message' => 'Pancake API key not configured.'];
        }

        // Using v1 endpoint as per user request
        $endpoint = $this->baseUrl . '/customers';
        $queryParams = [
            'api_key' => $this->apiKey,
            'phone_numbers' => json_encode([$phoneNumber]), // Keep as per API spec for phone_numbers query
        ];

        Log::info('Pancake API: Attempting to get customer by phone.', ['endpoint' => $endpoint, 'phone' => $phoneNumber]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($endpoint, $queryParams);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Pancake API: Get customer by phone response.', ['status' => $response->status(), 'response_data' => $responseData]);

                if (isset($responseData['success']) && $responseData['success'] && isset($responseData['data']) && is_array($responseData['data'])) {
                    if (!empty($responseData['data'])) {
                        return ['success' => true, 'data' => $responseData['data'][0], 'message' => 'Customer found on Pancake.'];
                    } else {
                        return ['success' => true, 'data' => null, 'message' => 'Customer not found on Pancake with this phone number.'];
                    }
                }
                $errorMessage = $responseData['message'] ?? (is_string($responseData) ? $responseData : 'Pancake API: Get customer by phone failed or unexpected response structure.');
                Log::warning('Pancake API: Get customer by phone failed or unexpected response.', ['response_data' => $responseData]);
                return ['success' => false, 'data' => null, 'message' => $errorMessage];

            } else {
                $errorBody = $response->json() ?? $response->body();
                $errorMessage = 'Pancake API: Get customer by phone HTTP request failed.';
                 if(is_array($errorBody) && isset($errorBody['message'])) {
                    $errorMessage = $errorBody['message'];
                } else if (is_string($errorBody) && !empty($errorBody)) {
                    $errorMessage = $errorBody;
                }
                Log::error('Pancake API: Get customer by phone HTTP request failed.', [
                    'status' => $response->status(),
                    'response_body' => $errorBody
                ]);
                return ['success' => false, 'data' => null, 'message' => $errorMessage, 'status_code' => $response->status()];
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Pancake API: RequestException during get customer by phone: ' . $e->getMessage(), ['phone' => $phoneNumber]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: RequestException - ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Pancake API: General Exception during get customer by phone: ' . $e->getMessage(), ['phone' => $phoneNumber]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: Exception - ' . $e->getMessage()];
        }
    }

    // =========== ADDED METHODS FOR ORDERS START HERE ===========

    /**
     * Create a new order on Pancake.
     *
     * @param array $data The order data, structured according to Pancake API requirements.
     *                    Expected keys include: ShopId, CustomerName, CustomerPhoneNumber, ShippingAddress (object),
     *                    Products (array), TotalAmount, ShippingFee, Note, Status (numeric), etc.
     * @return array ['success' => bool, 'data' => order_object|null, 'message' => string]
     */
    public function createOrderOnPancake(array $orderData): array
    {
        if (!$this->apiKey) {
            Log::error('Pancake API: API key not configured for creating order.');
            return ['success' => false, 'data' => null, 'message' => 'Pancake API key not configured.'];
        }

        $shopId = config('pancake.shop_id');
        if (empty($shopId)) {
            Log::error('Pancake API: Shop ID not configured.');
            return ['success' => false, 'data' => null, 'message' => 'Pancake shop_id not configured.'];
        }

        $endpoint = $this->baseUrl . '/shops/' . $shopId . '/orders';
        Log::info('Pancake API: Attempting to create order.', ['endpoint' => $endpoint, 'payload' => $orderData]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($endpoint . '?api_key=' . $this->apiKey, $orderData);

            $responseData = $response->json();
            Log::info('Pancake API: Create order response.', ['status' => $response->status(), 'response_data' => $responseData]);

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                    'message' => 'Order created successfully on Pancake.'
                ];
            }

            $errorMessage = 'Pancake API: Create order failed.';
            if (isset($responseData['message']) && is_string($responseData['message'])) {
                $errorMessage = $responseData['message'];
            } elseif (isset($responseData['error']) && is_string($responseData['error'])) {
                $errorMessage = $responseData['error'];
            } elseif (isset($responseData['errors']) && is_array($responseData['errors'])) {
                $firstError = reset($responseData['errors']);
                $errorMessage = is_string($firstError) ? $firstError : (isset($firstError['message']) ? $firstError['message'] : $errorMessage);
                 // Log all errors if available
                Log::warning('Pancake API: Create order validation errors.', ['errors' => $responseData['errors']]);
            } elseif (is_string($responseData)) {
                $errorMessage = $responseData;
            }

            Log::error('Pancake API: Create order failed.', [
                'status' => $response->status(),
                'response_body' => $responseData ?? $response->body()
            ]);
            return ['success' => false, 'data' => null, 'message' => $errorMessage, 'status_code' => $response->status()];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Pancake API: RequestException during order creation: ' . $e->getMessage(), ['payload' => $orderData, 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: RequestException - ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Pancake API: General Exception during order creation: ' . $e->getMessage(), ['payload' => $orderData, 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: Exception - ' . $e->getMessage()];
        }
    }

    /**
     * Update an existing order on Pancake.
     *
     * @param string $pancakeOrderId The Pancake ID of the order to update.
     * @param array $orderData The order data to update, structured according to Pancake API requirements.
     * @return array ['success' => bool, 'data' => order_object|null, 'message' => string]
     */
    public function updateOrderOnPancake(string $pancakeOrderId, array $orderData): array
    {
        $shopId = config('pancake.shop_id');
        if (!$this->apiKey) {
            Log::error('Pancake API: API key not configured for updating order.');
            return ['success' => false, 'data' => null, 'message' => 'Pancake API key not configured.'];
        }

        if (empty($pancakeOrderId)) {
            Log::error('Pancake API: Pancake Order ID is required for updating order.');
            return ['success' => false, 'data' => null, 'message' => 'Pancake Order ID is required.'];
        }

        $endpoint = $this->baseUrl . '/shops/' . $shopId . '/orders/' . $pancakeOrderId;
        Log::info('Pancake API: Attempting to update order.', ['endpoint' => $endpoint, 'pancake_order_id' => $pancakeOrderId, 'payload' => $orderData]);

        try {
            // Pancake API for update order uses PUT
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->put($endpoint . '?api_key=' . $this->apiKey, $orderData);

            $responseData = $response->json();

            Log::info('Pancake API: Update order response.', ['status' => $response->status(), 'response_data' => $responseData]);

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                    'message' => 'Order updated successfully on Pancake.'
                ];
            }

            $errorMessage = 'Pancake API: Update order failed.';
             if (isset($responseData['message']) && is_string($responseData['message'])) {
                $errorMessage = $responseData['message'];
            } elseif (isset($responseData['error']) && is_string($responseData['error'])) {
                $errorMessage = $responseData['error'];
            } elseif (isset($responseData['errors']) && is_array($responseData['errors'])) {
                $firstError = reset($responseData['errors']);
                $errorMessage = is_string($firstError) ? $firstError : (isset($firstError['message']) ? $firstError['message'] : $errorMessage);
                Log::warning('Pancake API: Update order validation errors.', ['errors' => $responseData['errors']]);
            } elseif (is_string($responseData)) {
                $errorMessage = $responseData;
            }


            Log::error('Pancake API: Update order failed.', [
                'status' => $response->status(),
                'response_body' => $responseData ?? $response->body()
            ]);
            return ['success' => false, 'data' => null, 'message' => $errorMessage, 'status_code' => $response->status()];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Pancake API: RequestException during order update: ' . $e->getMessage(), ['pancake_order_id' => $pancakeOrderId, 'payload' => $orderData, 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: RequestException - ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Pancake API: General Exception during order update: ' . $e->getMessage(), ['pancake_order_id' => $pancakeOrderId, 'payload' => $orderData, 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'data' => null, 'message' => 'Pancake API: Exception - ' . $e->getMessage()];
        }
    }
    // =========== ADDED METHODS FOR ORDERS END HERE ===========

    /**
     * Gửi GET request đến Pancake API
     */
    public function get($endpoint, $params = [])
    {
        try {
            $url = $this->buildUrl($endpoint);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($url);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Pancake API GET request failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi kết nối đến Pancake API: ' . $e->getMessage()
            ];
        }
    }
}
