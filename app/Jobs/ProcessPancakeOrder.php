<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPancakeOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderData;
    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $orderData, string $batchId)
    {
        $this->orderData = $orderData;
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            // Check if order exists
            $existingOrder = null;
            if (!empty($this->orderData['id'])) {
                $existingOrder = Order::where('pancake_order_id', $this->orderData['id'])->first();
            }

            if (!$existingOrder && !empty($this->orderData['code'])) {
                $existingOrder = Order::where('order_code', $this->orderData['code'])->first();
            }

            // Parse live session info from notes if exists
            $liveSessionInfo = $this->parseLiveSessionInfo($this->orderData['notes'] ?? null);

            // Prepare order data
            $orderData = [
                'pancake_order_id' => $this->orderData['id'] ?? null,
                'order_code' => $this->orderData['code'] ?? null,
                'customer_name' => $this->orderData['customer_name'] ?? null,
                'customer_phone' => $this->orderData['customer_phone'] ?? null,
                'total_amount' => $this->orderData['total_amount'] ?? 0,
                'status' => $this->orderData['status'] ?? 'pending',
                'notes' => $this->orderData['notes'] ?? null,
                'live_number' => $liveSessionInfo['live_number'] ?? null,
                'live_date' => $liveSessionInfo['session_date'] ?? null,
                'raw_data' => $this->orderData
            ];

            if ($existingOrder) {
                $existingOrder->update($orderData);
                $this->updateSyncStats('updated');
            } else {
                Order::create($orderData);
                $this->updateSyncStats('created');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing Pancake order in job', [
                'error' => $e->getMessage(),
                'order_data' => $this->orderData,
                'batch_id' => $this->batchId
            ]);
            $this->updateSyncStats('failed');
            throw $e;
        }
    }

    /**
     * Update sync statistics in cache
     */
    private function updateSyncStats(string $type)
    {
        $stats = cache()->get("pancake_sync_{$this->batchId}_stats", [
            'created' => 0,
            'updated' => 0,
            'failed' => 0
        ]);

        $stats[$type]++;
        cache()->put("pancake_sync_{$this->batchId}_stats", $stats, now()->addHours(2));
    }

    /**
     * Parse live session information from order notes
     */
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
}
