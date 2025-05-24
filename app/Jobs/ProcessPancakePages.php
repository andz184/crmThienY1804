<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessPancakePages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $page;
    protected $apiKey;
    protected $shopId;
    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $page, string $apiKey, string $shopId, string $batchId)
    {
        $this->page = $page;
        $this->apiKey = $apiKey;
        $this->shopId = $shopId;
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');
            $url = "{$baseUrl}/shops/{$this->shopId}/orders";

            $response = Http::timeout(30)->get($url, [
                'api_key' => $this->apiKey,
                'page' => $this->page,
                'limit' => 50
            ]);

            if (!$response->successful()) {
                Log::error('Error fetching orders page from Pancake', [
                    'page' => $this->page,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'batch_id' => $this->batchId
                ]);
                return;
            }

            $responseData = $response->json();

            // Process each order in this page
            foreach ($responseData['data'] ?? [] as $orderData) {
                ProcessPancakeOrder::dispatch($orderData, $this->batchId)
                    ->onQueue('pancake-sync');
            }

            // Update sync info
            $syncInfo = cache()->get("pancake_sync_{$this->batchId}_info");
            if ($syncInfo) {
                $syncInfo['current_page'] = $this->page;
                cache()->put("pancake_sync_{$this->batchId}_info", $syncInfo, now()->addHours(2));
            }

        } catch (\Exception $e) {
            Log::error('Error processing Pancake orders page', [
                'error' => $e->getMessage(),
                'page' => $this->page,
                'batch_id' => $this->batchId,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
