<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class PancakeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a staff user for testing
        $user = User::factory()->create();
        $user->assignRole('staff');
    }

    /** @test */
    public function it_can_handle_new_order_webhook()
    {
        $orderData = [
            'data' => [
                'order' => [
                    'id' => '12345',
                    'code' => 'PCK-TEST-001',
                    'status' => 'new',
                    'total' => 150000,
                    'shipping_fee' => 30000,
                    'customer' => [
                        'name' => 'Test Customer',
                        'phone' => '0123456789',
                        'email' => 'test@example.com'
                    ],
                    'items' => [
                        [
                            'name' => 'Test Product',
                            'code' => 'PRD-001',
                            'price' => 120000,
                            'quantity' => 1
                        ]
                    ],
                    'shipping_address' => [
                        'province_code' => '01',
                        'district_code' => '001',
                        'ward_code' => '00001',
                        'address' => '123 Test Street',
                        'full_address' => '123 Test Street, Ward 1, District 1, City'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/pancake', $orderData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => [
                        'order' => 'Order created successfully'
                    ]
                ]);

        $this->assertDatabaseHas('orders', [
            'pancake_order_id' => '12345',
            'order_code' => 'PCK-TEST-001',
            'status' => 'new',
            'total_value' => 150000,
            'shipping_fee' => 30000
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'phone' => '0123456789',
            'email' => 'test@example.com'
        ]);
    }

    /** @test */
    public function it_can_handle_order_update_webhook()
    {
        // Create existing order and customer
        $customer = Customer::create([
            'name' => 'Existing Customer',
            'phone' => '0123456789',
            'email' => 'existing@example.com'
        ]);

        $order = Order::create([
            'pancake_order_id' => '12345',
            'order_code' => 'PCK-TEST-001',
            'status' => 'new',
            'total_value' => 150000,
            'shipping_fee' => 30000,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email
        ]);

        $updateData = [
            'data' => [
                'order' => [
                    'id' => '12345',
                    'code' => 'PCK-TEST-001',
                    'status' => 'completed',
                    'total' => 150000,
                    'shipping_fee' => 30000,
                    'tracking_code' => 'TRACK123',
                    'customer' => [
                        'name' => 'Existing Customer',
                        'phone' => '0123456789',
                        'email' => 'existing@example.com'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/pancake', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => [
                        'order' => 'Order updated successfully'
                    ]
                ]);

        $this->assertDatabaseHas('orders', [
            'pancake_order_id' => '12345',
            'status' => 'completed',
            'tracking_code' => 'TRACK123'
        ]);
    }

    /** @test */
    public function it_can_handle_new_customer_webhook()
    {
        $customerData = [
            'data' => [
                'customer' => [
                    'id' => '67890',
                    'name' => 'New Customer',
                    'phone' => '9876543210',
                    'email' => 'new@example.com',
                    'address' => '456 New Street'
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/pancake', $customerData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => [
                        'customer' => 'Customer processed successfully'
                    ]
                ]);

        $this->assertDatabaseHas('customers', [
            'pancake_id' => '67890',
            'name' => 'New Customer',
            'phone' => '9876543210',
            'email' => 'new@example.com',
            'address' => '456 New Street'
        ]);
    }

    /** @test */
    public function it_can_handle_customer_update_webhook()
    {
        // Create existing customer
        $customer = Customer::create([
            'pancake_id' => '67890',
            'name' => 'Old Name',
            'phone' => '9876543210',
            'email' => 'old@example.com',
            'address' => '456 Old Street'
        ]);

        $updateData = [
            'data' => [
                'customer' => [
                    'id' => '67890',
                    'name' => 'Updated Name',
                    'phone' => '9876543210',
                    'email' => 'updated@example.com',
                    'address' => '456 New Street'
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/pancake', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => [
                        'customer' => 'Customer processed successfully'
                    ]
                ]);

        $this->assertDatabaseHas('customers', [
            'pancake_id' => '67890',
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'address' => '456 New Street'
        ]);
    }

    /** @test */
    public function it_handles_invalid_webhook_data()
    {
        $invalidData = [
            'data' => [
                'invalid' => 'data'
            ]
        ];

        $response = $this->postJson('/api/webhooks/pancake', $invalidData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'No recognizable data found in webhook'
                ]);
    }

    /** @test */
    public function it_handles_missing_required_fields()
    {
        $incompleteOrderData = [
            'data' => [
                'order' => [
                    'id' => '12345'
                    // Missing other required fields
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/pancake', $incompleteOrderData);

        $response->assertStatus(200); // The webhook still processes but logs warnings

        // Verify that no order was created
        $this->assertDatabaseMissing('orders', [
            'pancake_order_id' => '12345'
        ]);
    }

    /** @test */
    public function it_logs_webhook_processing()
    {
        Log::shouldReceive('info')
            ->with('Received Pancake webhook', \Mockery::any())
            ->once();

        $orderData = [
            'data' => [
                'order' => [
                    'id' => '12345',
                    'code' => 'PCK-TEST-001',
                    'status' => 'new',
                    'customer' => [
                        'name' => 'Test Customer',
                        'phone' => '0123456789'
                    ]
                ]
            ]
        ];

        $this->postJson('/api/webhooks/pancake', $orderData);
    }
}
