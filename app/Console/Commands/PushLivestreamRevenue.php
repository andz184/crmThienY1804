<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Pusher\Pusher;

class PushLivestreamRevenue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestream:push-revenue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from Google Sheet and push to Pusher for livestream display.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to fetch and push livestream data...');

        $spreadsheetId = config('services.google.sheet_id');
        $range = config('services.google.sheet_range');

        $this->info("Configuration check: Trying to use range -> " . $range);

        if (!$spreadsheetId || !$range) {
            $this->error('Google Sheet ID or Range is not configured in .env file.');
            return 1;
        }

        $credentialsPath = storage_path('app/google-credentials.json');
        if (!file_exists($credentialsPath)) {
            $this->error('`google-credentials.json` not found in `storage/app/`.');
            return 1;
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(GoogleSheets::SPREADSHEETS_READONLY);
            $sheets = new GoogleSheets($client);
            $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            $this->info('Data received from Google Sheets: ' . ($values ? count($values) : 0) . ' rows.');

            $totalRevenue = 0;
            $topProducts = [];
            $topProvinces = [];
            $latestOrder = null;
            $processedRows = 0;

            foreach ($values as $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                $processedRows++;

                // Corrected column indices based on user feedback
                // J=9, K=10, L=11, M=12, V=21, A=0, D=3
                $quantity = $this->cleanNumber(isset($row[9]) ? $row[9] : '0');      // Col J: Số lượng
                $price = $this->cleanNumber(isset($row[10]) ? $row[10] : '0');       // Col K: Giá
                $transfer = $this->cleanNumber(isset($row[12]) ? $row[12] : '0');    // Col M: Tiền chuyển khoản
                $shipFee = $this->cleanNumber(isset($row[21]) ? $row[21] : '0');     // Col V: Phí ship

                // Calculate revenue for this row (one order)
                $rowRevenue = ($price * $quantity) - $transfer + $shipFee;
                $totalRevenue += abs($rowRevenue); // Use absolute value to ensure positive revenue

                // Aggregate data for Top 5 Products
                $productName = isset($row[8]) ? trim($row[8]) : 'Không có tên'; // Col I: Tên sản phẩm
                if (!empty($productName)) {
                    if (!isset($topProducts[$productName])) {
                        $topProducts[$productName] = ['revenue' => 0, 'count' => 0];
                    }
                    $topProducts[$productName]['revenue'] += abs($rowRevenue);
                    $topProducts[$productName]['count']++;
                }
                
                // Aggregate data for Top 5 Provinces
                $province = isset($row[3]) ? trim($row[3]) : 'Không rõ'; // Col D: Tỉnh thành
                if (!empty($province)) {
                    if (!isset($topProvinces[$province])) {
                        $topProvinces[$province] = ['revenue' => 0, 'count' => 0];
                    }
                    $topProvinces[$province]['revenue'] += abs($rowRevenue);
                    $topProvinces[$province]['count']++;
                }

                // Capture details for the latest order
                $customerName = isset($row[0]) ? trim($row[0]) : '';  // Col A: Tên khách hàng
                $productName = isset($row[8]) ? trim($row[8]) : '';   // Col I: Tên sản phẩm
                $province = isset($row[3]) ? trim($row[3]) : '';      // Col D: Tỉnh thành
                $status = isset($row[23]) ? trim($row[23]) : '';      // Col X: Trạng thái

                // Only update latest order if all required fields are present
                if (!empty($customerName) && !empty($productName) && !empty($province) && !empty($status)) {
                    $latestOrder = [
                        'customer_name' => $customerName,
                        'product_name'  => $productName,
                        'province'      => $province,
                        'status'        => $status,
                    ];
                }
            }

            // Sort by revenue in descending order and get top 5
            arsort($topProducts);
            arsort($topProvinces);
            
            $finalTopProducts = array_slice($topProducts, 0, 5, true);
            $finalTopProvinces = array_slice($topProvinces, 0, 5, true);

            $this->info("Calculation result: Revenue = " . number_format($totalRevenue) . ", Orders = " . $processedRows);
            if ($latestOrder) {
                $this->info("Latest order details: " . json_encode($latestOrder));
            }
            
            // Push to Pusher
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

            $this->info('Successfully pushed update to Pusher channel `livestream-channel`.');

        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
        }

        return 0;
    }

    /**
     * Cleans a string to get a valid number.
     * It handles formats like "1.500.000" or "1,500,000".
     */
    private function cleanNumber($string)
    {
        if (is_null($string) || $string === '') {
            return 0;
        }
        // Remove thousands separators (dots or commas) and keep the decimal separator if any.
        // This is a robust way to handle Vietnamese currency formats.
        $string = str_replace('.', '', $string);
        // In case a comma is used as a decimal separator, replace it with a dot.
        // For this app, we assume integer values, so we can also remove commas.
        $string = str_replace(',', '', $string);

        // Remove any other non-numeric characters except for a potential leading minus sign.
        return (float)filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}

