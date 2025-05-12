<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait PancakeApi
{
    protected function getPancakeBaseUrl()
    {
        return rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1/'), '/');
    }

    protected function getPancakeApiKey()
    {
        return config('pancake.api_key');
    }

    protected function getPancakeShopId()
    {
        return config('pancake.shop_id');
    }

    protected function makePancakeRequest($endpoint, $method = 'GET', $data = [])
    {
        $baseUrl = $this->getPancakeBaseUrl();
        $apiKey = $this->getPancakeApiKey();
        $shopId = $this->getPancakeShopId();

        if (empty($apiKey) || empty($shopId)) {
            Log::error('Pancake API configuration missing', [
                'api_key_exists' => !empty($apiKey),
                'shop_id_exists' => !empty($shopId)
            ]);
            return null;
        }

        $url = $baseUrl . '/' . trim($endpoint, '/');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->withQueryParameters([
                'api_key' => $apiKey,
                'shop_id' => $shopId,
            ])->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Pancake API Error', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Pancake API Exception', [
                'url' => $url,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }
}
