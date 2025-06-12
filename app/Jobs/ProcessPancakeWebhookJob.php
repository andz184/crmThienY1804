<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PancakeWebhookService;
use Illuminate\Support\Facades\Log;

class ProcessPancakeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The webhook data.
     *
     * @var array
     */
    protected $webhookData;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param array $webhookData
     */
    public function __construct(array $webhookData)
    {
        $this->webhookData = $webhookData;
    }

    /**
     * Execute the job.
     *
     * @param PancakeWebhookService $webhookService
     * @return void
     */
    public function handle(PancakeWebhookService $webhookService): void
    {
        try {
            Log::info('Processing Pancake webhook job.', ['pancake_order_id' => $this->webhookData['id'] ?? 'N/A']);
            $webhookService->processWebhook($this->webhookData);
            Log::info('Successfully processed Pancake webhook job.', ['pancake_order_id' => $this->webhookData['id'] ?? 'N/A']);
        } catch (\Exception $e) {
            Log::error('Error processing Pancake webhook job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->webhookData
            ]);

            // Release the job back onto the queue for a retry
            $this->release(60); // Delay for 60 seconds
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return 60 * $this->attempts();
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->webhookData['id'];
    }
}
