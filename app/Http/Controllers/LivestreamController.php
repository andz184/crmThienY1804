<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Pusher\Pusher;

class LivestreamController extends Controller
{
    public function triggerUpdate(Request $request)
    {
        try {
            $spreadsheetId = config('services.google.sheet_id');
            $range = config('services.google.sheet_range');

            if (!$spreadsheetId || !$range) {
                return response()->json(['status' => 'error', 'message' => 'Google Sheet ID or Range is not configured.'], 500);
            }

            $credentialsPath = storage_path('app/google-credentials.json');
            if (!file_exists($credentialsPath)) {
                return response()->json(['status' => 'error', 'message' => '`google-credentials.json` not found.'], 500);
            }

            $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(GoogleSheets::SPREADSHEETS_READONLY);
            $sheets = new GoogleSheets($client);
            $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues() ?: [];

            $totalRevenue = 0;
            $topProducts = [];
            $topProvinces = [];
            $latestOrder = null;
            $processedRows = 0;

            foreach ($values as $row) {
                if (empty(array_filter($row))) continue;
                $processedRows++;

                $quantity = $this->cleanNumber(isset($row[9]) ? $row[9] : '0');
                $price = $this->cleanNumber(isset($row[10]) ? $row[10] : '0');
                $transfer = $this->cleanNumber(isset($row[12]) ? $row[12] : '0');
                $shipFee = $this->cleanNumber(isset($row[21]) ? $row[21] : '0');
                $rowRevenue = ($price * $quantity) - $transfer + $shipFee;
                $totalRevenue += abs($rowRevenue);

                $productName = isset($row[8]) ? trim($row[8]) : 'Không có tên';
                if (!empty($productName)) {
                    if (!isset($topProducts[$productName])) {
                        $topProducts[$productName] = ['revenue' => 0, 'count' => 0];
                    }
                    $topProducts[$productName]['revenue'] += abs($rowRevenue);
                    $topProducts[$productName]['count']++;
                }

                $province = isset($row[3]) ? trim($row[3]) : 'Không rõ';
                if (!empty($province)) {
                    if (!isset($topProvinces[$province])) {
                        $topProvinces[$province] = ['revenue' => 0, 'count' => 0];
                    }
                    $topProvinces[$province]['revenue'] += abs($rowRevenue);
                    $topProvinces[$province]['count']++;
                }

                $customerName = isset($row[0]) ? trim($row[0]) : '';
                $productNameLatest = isset($row[8]) ? trim($row[8]) : '';
                $provinceLatest = isset($row[3]) ? trim($row[3]) : '';
                $status = isset($row[23]) ? trim($row[23]) : '';

                if (!empty($customerName) && !empty($productNameLatest) && !empty($provinceLatest) && !empty($status)) {
                    $latestOrder = [
                        'customer_name' => $customerName,
                        'product_name'  => $productNameLatest,
                        'province'      => $provinceLatest,
                        'status'        => $status,
                    ];
                }
            }

            // Sort by revenue in descending order using a custom sort function
            uasort($topProducts, function ($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });
            uasort($topProvinces, function ($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });
            
            $finalTopProducts = array_slice($topProducts, 0, 5, true);
            $finalTopProvinces = array_slice($topProvinces, 0, 5, true);

            $options = [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => true
            ];
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                $options
            );

            $data = [
                'total_revenue' => $totalRevenue,
                'total_orders' => $processedRows,
                'latest_order' => $latestOrder,
                'top_products' => $finalTopProducts,
                'top_provinces' => $finalTopProvinces,
            ];
            $pusher->trigger('livestream-channel', 'livestream-update', $data);

            return response()->json(['status' => 'success', 'message' => 'Update pushed successfully.']);

        } catch (\Exception $e) {
            Log::error("Livestream Update Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['status' => 'error', 'message' => 'An internal server error occurred.'], 500);
        }
    }

    private function cleanNumber($string)
    {
        if (is_null($string) || $string === '') return 0;
        $string = str_replace(['.', ','], '', $string);
        return (float)filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
