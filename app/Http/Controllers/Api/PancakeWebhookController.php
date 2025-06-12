<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPancakeWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PancakeWebhookController extends Controller
{
    /**
     * Handle all incoming webhooks from Pancake.
     * The webhook is queued for background processing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        // Log the incoming webhook for auditing purposes
        Log::info('Received Pancake webhook', [
            'data' => $request->all()
        ]);

        $webhookData = $request->all();

        // Basic validation before dispatching the job
        $validator = Validator::make($webhookData, [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            Log::error('Pancake webhook failed validation', [
                'errors' => $validator->errors(),
                'data' => $webhookData
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Webhook validation failed: Missing required field `id`.'
            ], 400);
        }

        try {
            // Dispatch the job to the 'webhooks' queue
            ProcessPancakeWebhookJob::dispatch($webhookData)->onQueue('webhooks');

            // Return an immediate success response to Pancake
            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued for processing.'
            ]);

        } catch (\Exception $e) {
            Log::critical('Failed to dispatch Pancake webhook job to the queue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $webhookData
            ]);

            // Return a server error response if the job cannot be dispatched
            return response()->json([
                'success' => false,
                'message' => 'Could not queue webhook for processing. Please check system logs.'
            ], 500);
        }
    }
}