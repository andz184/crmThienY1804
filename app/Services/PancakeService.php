<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PancakeService
{
    protected $baseUrl;
    protected $apiKey;
    protected $shopId;
    protected $progressKey = 'pancake_sync_progress';
    protected $statsKey = 'pancake_sync_stats';
    protected $lastSyncKey = 'pancake_last_sync';
    protected $timeout = 7200; // Tăng timeout lên 2 giờ

    public function __construct()
    {
        $this->baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');
        $this->apiKey = config('pancake.api_key');
        $this->shopId = config('pancake.shop_id');

        if (empty($this->apiKey) || empty($this->shopId)) {
            throw new \Exception('Pancake API key và Shop ID là bắt buộc. Vui lòng kiểm tra file .env');
        }
    }

    public function getProgress()
    {
        return [
            'progress' => Cache::get($this->progressKey, 0),
            'stats' => Cache::get($this->statsKey, [
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
                'no_phone' => 0,
                'errors' => []
            ]),
            'is_completed' => Cache::get($this->progressKey) === 100,
            'message' => Cache::get($this->progressKey) === 100 ? 'Hoàn thành đồng bộ' : 'Đang đồng bộ...',
            'last_sync' => Cache::get($this->lastSyncKey)
        ];
    }

    public function cancelSync()
    {
        Cache::forget($this->progressKey);
        Cache::forget($this->statsKey);
        Cache::forget($this->lastSyncKey);
    }

    protected function updateProgress($progress, $stats)
    {
        Cache::put($this->progressKey, $progress, now()->addHours(1));
        Cache::put($this->statsKey, $stats, now()->addHours(1));
    }

    public function syncCustomers()
    {
        try {
            // Set execution time
            set_time_limit(3600); // 1 hour
            ini_set('memory_limit', '512M');

            // Reset progress
            $this->updateProgress(0, [
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
                'no_phone' => 0,
                'errors' => []
            ]);

            $startTime = microtime(true);
            Log::info('Bắt đầu quá trình đồng bộ');

            // Get last sync time
            $lastSync = Cache::get($this->lastSyncKey);
            $params = [
                'api_key' => $this->apiKey,
                'page_size' => 1000,
               
            ];

            // If we have last sync time, only get customers updated after that
            if ($lastSync) {
                $params['updated_after'] = $lastSync;
            }

            // Kiểm tra kết nối đến Pancake API
            $testResponse = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->withQueryParameters($params)->get($this->baseUrl . '/shops/' . $this->shopId . '/customers');

            if (!$testResponse->successful()) {
                $errorBody = $testResponse->json();
                Log::error('Không thể kết nối đến Pancake API', [
                    'status' => $testResponse->status(),
                    'error' => $errorBody
                ]);
                return [
                    'success' => false,
                    'message' => 'Không thể kết nối đến Pancake API: ' . ($errorBody['message'] ?? 'Unknown error')
                ];
            }

            $stats = [
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
                'no_phone' => 0,
                'errors' => []
            ];

            $currentPage = 1;
            $totalPages = null;

            do {
                // Check if sync was cancelled
                if (!Cache::has($this->progressKey)) {
                    Log::info('Đồng bộ đã bị hủy bởi người dùng');
                    return [
                        'success' => false,
                        'message' => 'Đồng bộ đã bị hủy'
                    ];
                }

                $pageStartTime = microtime(true);
                try {
                    Log::info("Đang gọi API cho trang {$currentPage}");

                    $response = Http::timeout($this->timeout)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->withQueryParameters(array_merge($params, ['page' => $currentPage]))
                        ->get($this->baseUrl . '/shops/' . $this->shopId . '/customers');

                    if (!$response->successful()) {
                        throw new \Exception('API request failed: ' . $response->status());
                    }

                    $pageData = $response->json();

                    if (!isset($pageData['data']) || !is_array($pageData['data'])) {
                        throw new \Exception('Invalid response format');
                    }

                    $totalPages = $pageData['total_pages'] ?? 1;
                    $totalEntries = $pageData['total_entries'] ?? 0;

                    // Update progress
                    $progress = round(($currentPage / $totalPages) * 100);
                    $this->updateProgress($progress, $stats);

                    Log::info("Dữ liệu nhận được từ trang {$currentPage}", [
                        'data_count' => count($pageData['data']),
                        'total_pages' => $totalPages,
                        'total_entries' => $totalEntries
                    ]);

                    DB::beginTransaction();

                    try {
                        foreach ($pageData['data'] as $customerData) {
                            $pancakeId = $customerData['id'] ?? null;

                            if (empty($pancakeId)) {
                                Log::warning('Bỏ qua khách hàng không có Pancake ID', [
                                    'data' => $customerData
                                ]);
                                continue;
                            }

                            $phoneNumbers = collect($customerData['phone_numbers'] ?? [])->filter();

                            if ($phoneNumbers->isEmpty()) {
                                Log::warning("Khách hàng không có số điện thoại", [
                                    'pancake_id' => $pancakeId,
                                    'name' => $customerData['name'] ?? 'N/A'
                                ]);
                                $stats['no_phone']++;
                                continue;
                            }

                            // Tìm khách hàng theo Pancake ID hoặc số điện thoại
                            $customer = Customer::where('pancake_id', $pancakeId)
                                ->orWhereHas('phones', function($query) use ($phoneNumbers) {
                                    $query->whereIn('phone_number', $phoneNumbers);
                                })
                                ->first();

                            // Chuẩn bị dữ liệu khách hàng
                            $customerAttributes = [
                                'pancake_id' => $pancakeId,
                                'name' => $customerData['name'] ?? 'Khách hàng không tên',
                                'email' => $customerData['emails'][0] ?? null,
                                'date_of_birth' => $customerData['date_of_birth'] ?? null,
                                'gender' => $customerData['gender'] ?? null,
                                'fb_id' => $customerData['fb_id'] ?? null,
                                'referral_code' => $customerData['referral_code'] ?? null,
                                'reward_point' => $customerData['reward_point'] ?? 0,
                                'total_orders_count' => $customerData['order_count'] ?? 0,
                                'succeed_order_count' => $customerData['succeed_order_count'] ?? 0,
                                'total_spent' => $customerData['purchased_amount'] ?? 0,
                                'last_order_at' => $customerData['last_order_at'] ?? null,
                                'tags' => $customerData['tags'] ?? [],
                                'addresses' => $customerData['shop_customer_addresses'] ?? []
                            ];

                            // Xử lý địa chỉ chính
                            if (!empty($customerData['shop_customer_addresses'])) {
                                $primaryAddress = $customerData['shop_customer_addresses'][0];
                                $customerAttributes = array_merge($customerAttributes, [
                                    'full_address' => $primaryAddress['full_address'] ?? null,
                                    'province' => $primaryAddress['province_id'] ?? null,
                                    'district' => $primaryAddress['district_id'] ?? null,
                                    'ward' => $primaryAddress['commune_id'] ?? null,
                                    'street_address' => $primaryAddress['address'] ?? null,
                                ]);
                            }

                            try {
                                if ($customer) {
                                    $customer->update($customerAttributes);
                                    Log::info("Cập nhật khách hàng", [
                                        'crm_id' => $customer->id,
                                        'pancake_id' => $pancakeId
                                    ]);
                                } else {
                                    $customer = Customer::create($customerAttributes);
                                    Log::info("Tạo mới khách hàng", [
                                        'crm_id' => $customer->id,
                                        'pancake_id' => $pancakeId
                                    ]);
                                }

                                // Xử lý số điện thoại
                                foreach ($phoneNumbers as $index => $phoneNumber) {
                                    $customer->phones()->updateOrCreate(
                                        ['phone_number' => $phoneNumber],
                                        [
                                            'is_primary' => $index === 0,
                                            'type' => 'mobile'
                                        ]
                                    );
                                }

                                $stats['synced']++;
                                $this->updateProgress($progress, $stats);

                                // Update last sync time for each successful customer sync
                                Cache::put($this->lastSyncKey, now()->toIso8601String(), now()->addDays(30));
                            } catch (\Exception $e) {
                                Log::error("Lỗi xử lý khách hàng", [
                                    'pancake_id' => $pancakeId,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                $stats['failed']++;
                                $stats['errors'][] = "Lỗi xử lý khách hàng - Pancake ID: {$pancakeId} - " . $e->getMessage();
                                continue;
                            }
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }

                    $currentPage++;
                } catch (\Exception $e) {
                    Log::error("Lỗi xử lý trang {$currentPage}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    if ($currentPage === 1) {
                        $this->cancelSync();
                        return [
                            'success' => false,
                            'message' => "Lỗi xử lý trang {$currentPage}: " . $e->getMessage()
                        ];
                    }

                    break;
                }
            } while ($currentPage <= $totalPages);

            // Set final progress
            $this->updateProgress(100, $stats);

            $totalDuration = round(microtime(true) - $startTime, 2);
            $message = $lastSync
                ? "Đã đồng bộ thành công {$stats['synced']} khách hàng mới/cập nhật từ lần đồng bộ trước."
                : "Đã đồng bộ thành công {$stats['synced']}/{$totalEntries} khách hàng (trong {$totalDuration} giây)";

            if ($stats['failed'] > 0) {
                $message .= ". {$stats['failed']} khách hàng bị lỗi.";
            }
            if ($stats['no_phone'] > 0) {
                $message .= ". {$stats['no_phone']} khách hàng không có SĐT.";
            }

            Log::info('Kết thúc đồng bộ', [
                'total_duration' => $totalDuration,
                'stats' => $stats
            ]);

            return [
                'success' => true,
                'message' => $message,
                'stats' => $stats,
                'duration' => $totalDuration,
                'is_initial_sync' => !$lastSync
            ];

        } catch (\Exception $e) {
            $this->cancelSync();
            Log::error('Lỗi đồng bộ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Lỗi đồng bộ: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCustomersFromDatabase(Request $request)
    {
        // Query customers directly from database without syncing
        $query = Customer::query();

        if ($request->filled('search')) {
            $searchTerm = strtolower($request->input('search'));
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('phones', function($q) use ($searchTerm) {
                      $q->where('phone_number', 'like', "%{$searchTerm}%");
                  })
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Create a new customer in Pancake
     */
    public function createCustomer(array $data)
    {
        try {
            // Format data according to Pancake API requirements
            $pancakeData = [
                'name' => $data['name'],
                'phoneNumber' => $data['phone'],
                'createType' => 'ignore'
            ];

            // Add optional fields if they exist
            if (!empty($data['date_of_birth'])) {
                $pancakeData['dateOfBirth'] = $data['date_of_birth'];
            }

            // Add address if exists
            if (!empty($data['full_address'])) {
                $pancakeData['addresses'] = [[
                    'address' => $data['street_address'] ?? '',
                    'province_id' => $data['province'] ?? '',
                    'district_id' => $data['district'] ?? '',
                    'commune_id' => $data['ward'] ?? '',
                    'full_address' => $data['full_address'],
                    'is_default' => true
                ]];
            }

            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/customers", array_merge($pancakeData, [
                    'api_key' => $this->apiKey,
                    'shop_id' => $this->shopId
                ]));

            $result = $response->json();

            if (!$response->successful()) {
                Log::error('Pancake API Error - Create Customer', [
                    'status' => $response->status(),
                    'response' => $result,
                    'data' => $pancakeData
                ]);
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Unknown error from Pancake API'
                ];
            }

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (\Exception $e) {
            Log::error('Pancake API Exception - Create Customer', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'success' => false,
                'message' => 'Connection error to Pancake API'
            ];
        }
    }

    /**
     * Update an existing customer in Pancake
     */
    public function updateCustomer(string $pancakeId, array $data)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->put("{$this->baseUrl}/customers/{$pancakeId}", array_merge($data, [
                    'api_key' => $this->apiKey,
                    'shop_id' => $this->shopId
                ]));

            $result = $response->json();

            if (!$response->successful()) {
                Log::error('Pancake API Error - Update Customer', [
                    'status' => $response->status(),
                    'response' => $result,
                    'pancake_id' => $pancakeId,
                    'data' => $data
                ]);
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Unknown error from Pancake API'
                ];
            }

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (\Exception $e) {
            Log::error('Pancake API Exception - Update Customer', [
                'message' => $e->getMessage(),
                'pancake_id' => $pancakeId,
                'data' => $data
            ]);
            return [
                'success' => false,
                'message' => 'Connection error to Pancake API'
            ];
        }
    }

    /**
     * Get customer details from Pancake
     */
    public function getCustomer(string $pancakeId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/customers/{$pancakeId}", [
                    'api_key' => $this->apiKey,
                    'shop_id' => $this->shopId
                ]);

            $result = $response->json();

            if (!$response->successful()) {
                Log::error('Pancake API Error - Get Customer', [
                    'status' => $response->status(),
                    'response' => $result,
                    'pancake_id' => $pancakeId
                ]);
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Unknown error from Pancake API'
                ];
            }

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (\Exception $e) {
            Log::error('Pancake API Exception - Get Customer', [
                'message' => $e->getMessage(),
                'pancake_id' => $pancakeId
            ]);
            return [
                'success' => false,
                'message' => 'Connection error to Pancake API'
            ];
        }
    }

    /**
     * Get list of customers from Pancake with pagination
     */
    public function getCustomers(array $params = [])
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/customers", array_merge($params, [
                    'api_key' => $this->apiKey,
                    'shop_id' => $this->shopId,
                    'per_page' => 100
                ]));

            $result = $response->json();

            if (!$response->successful()) {
                Log::error('Pancake API Error - Get Customers List', [
                    'status' => $response->status(),
                    'response' => $result,
                    'params' => $params
                ]);
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Unknown error from Pancake API'
                ];
            }

            return [
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Pancake API Exception - Get Customers List', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);
            return [
                'success' => false,
                'message' => 'Connection error to Pancake API'
            ];
        }
    }

    /**
     * Delete a customer from Pancake
     */
    public function deleteCustomer(string $pancakeId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete("{$this->baseUrl}/customers/{$pancakeId}", [
                    'api_key' => $this->apiKey,
                    'shop_id' => $this->shopId
                ]);

            $result = $response->json();

            if (!$response->successful()) {
                Log::error('Pancake API Error - Delete Customer', [
                    'status' => $response->status(),
                    'response' => $result,
                    'pancake_id' => $pancakeId
                ]);
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Unknown error from Pancake API'
                ];
            }

            return [
                'success' => true,
                'data' => $result['data'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Pancake API Exception - Delete Customer', [
                'message' => $e->getMessage(),
                'pancake_id' => $pancakeId
            ]);
            return [
                'success' => false,
                'message' => 'Connection error to Pancake API'
            ];
        }
    }
}
