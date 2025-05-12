<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\PancakeApi;
use Illuminate\Support\Facades\Log;

class TestPancakeApi extends Command
{
    use PancakeApi;

    protected $signature = 'pancake:test';
    protected $description = 'Test Pancake API integration';

    public function handle()
    {
        $this->info('Testing Pancake API integration...');

        // Test 1: Get shop information
        $this->info('1. Testing shop information...');
        $shopResponse = $this->makePancakeRequest('shops');
        if (!$shopResponse || !isset($shopResponse['success']) || !$shopResponse['success']) {
            $this->error('Failed to get shop information');
            Log::error('Pancake API Test - Shop Info Failed', ['response' => $shopResponse]);
            return 1;
        }
        $this->info('✓ Shop information retrieved successfully');

        // Test 2: Get customers list
        $this->info('2. Testing customers list...');
        $customersResponse = $this->makePancakeRequest('customers');
        if (!$customersResponse || !isset($customersResponse['data'])) {
            $this->error('Failed to get customers list');
            Log::error('Pancake API Test - Customers List Failed', ['response' => $customersResponse]);
            return 1;
        }
        $this->info('✓ Customers list retrieved successfully');

        // Test 3: Create a test customer
        $this->info('3. Testing customer creation...');
        $testCustomer = [
            'name' => 'Test Customer ' . time(),
            'phone' => '0987' . rand(100000, 999999),
            'email' => 'test' . time() . '@example.com',
            'address' => 'Test Address',
            'source' => 'API Test',
            'tags' => ['test', 'api'],
        ];

        $createResponse = $this->makePancakeRequest('customers', 'POST', $testCustomer);
        if (!$createResponse || !isset($createResponse['success']) || !$createResponse['success']) {
            $this->error('Failed to create test customer');
            Log::error('Pancake API Test - Create Customer Failed', [
                'request' => $testCustomer,
                'response' => $createResponse
            ]);
            return 1;
        }
        $this->info('✓ Test customer created successfully');

        $this->info('All tests completed successfully!');
        return 0;
    }
}
