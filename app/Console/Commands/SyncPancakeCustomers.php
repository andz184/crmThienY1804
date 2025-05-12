<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Traits\PancakeApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncPancakeCustomers extends Command
{
    use PancakeApi;

    protected $signature = 'pancake:sync-customers {--chunk=100} {--force}';
    protected $description = 'Synchronize customers with Pancake API';

    public function handle()
    {
        $this->info('Starting Pancake customer synchronization...');
        $chunk = $this->option('chunk');
        $force = $this->option('force');

        try {
            // Get customers from Pancake API
            $response = $this->makePancakeRequest('customers');

            if (!$response || !isset($response['success']) || !$response['success']) {
                $this->error('Failed to fetch customers from Pancake API');
                Log::error('Pancake Sync: Failed to fetch customers', ['response' => $response]);
                return 1;
            }

            $customers = $response['customers'] ?? [];
            $total = count($customers);
            $this->info("Found {$total} customers in Pancake");

            $bar = $this->output->createProgressBar($total);
            $syncedCount = 0;
            $errorCount = 0;

            // Process customers in chunks
            foreach (array_chunk($customers, $chunk) as $customerChunk) {
                DB::beginTransaction();
                try {
                    foreach ($customerChunk as $customerData) {
                        if (!isset($customerData['id'])) {
                            $this->warn('Customer data missing ID, skipping.');
                            $errorCount++;
                            continue;
                        }

                        // Check if customer exists
                        $customer = Customer::where('pancake_id', $customerData['id'])->first();

                        // If customer exists and force is false, skip
                        if ($customer && !$force) {
                            $bar->advance();
                            continue;
                        }

                        // Prepare customer data
                        $customerAttributes = [
                            'pancake_id' => $customerData['id'],
                            'name' => $customerData['name'] ?? null,
                            'phone' => $customerData['phone'] ?? null,
                            'email' => $customerData['email'] ?? null,
                            'address' => $customerData['address'] ?? null,
                            'facebook' => $customerData['facebook'] ?? null,
                            'zalo' => $customerData['zalo'] ?? null,
                            'telegram' => $customerData['telegram'] ?? null,
                            'status' => $customerData['status'] ?? 'active',
                            'raw_data' => $customerData,
                        ];

                        // Create or update customer
                        if ($customer) {
                            $customer->update($customerAttributes);
                        } else {
                            Customer::create($customerAttributes);
                        }

                        $syncedCount++;
                        $bar->advance();
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Error processing chunk: " . $e->getMessage());
                    Log::error('Pancake Sync: Error processing customer chunk', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorCount++;
                }
            }

            $bar->finish();
            $this->newLine();
            $this->info("Sync completed: {$syncedCount} customers synchronized, {$errorCount} errors");

            return 0;
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            Log::error('Pancake Sync: Fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
