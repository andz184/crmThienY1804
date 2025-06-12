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
        $this->info('Starting to fetch and push livestream data in a real-time loop...');

        try {
            $spreadsheetId = config('services.google.sheet_id');
            $range = config('services.google.sheet_range');

            if (!$spreadsheetId || !$range) {
                $this->error('Google Sheet ID or Range is not configured in .env file.');
                return 1;
            }

            $this->info('Initializing Google Client...');
            $credentialsPath = storage_path('app/google-credentials.json');
            if (!file_exists($credentialsPath)) {
                $this->error('`google-credentials.json` not found in `storage/app/`.');
                return 1;
            }
            $googleClient = new GoogleClient();
            $googleClient->setAuthConfig($credentialsPath);
            $googleClient->addScope(GoogleSheets::SPREADSHEETS_READONLY);
            $sheets = new GoogleSheets($googleClient);
            $this->info('Google Client initialized.');

            $this->info('Initializing Pusher Client...');
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
            $this->info('Pusher Client initialized.');
        } catch (\Exception $e) {
            $this->error("Failed to initialize clients: " . $e->getMessage());
            return 1;
        }

        while (true) {
            $startTime = microtime(true);
            try {
                $this->info('Fetching data from Google Sheets...');
                $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
                $values = $response->getValues();

                $totalRevenue = 0;
                $topProducts = [];
                $topProvinces = [];
                $latestOrder = null;
                $processedRows = 0;

                if ($values) {
                    foreach ($values as $row) {
                        if (empty(array_filter($row))) continue; // Skip empty rows
                        $processedRows++;

                        // J=9, K=10, L=11, M=12, V=21, A=0, D=3, I=8, X=23
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
                        $productNameValue = isset($row[8]) ? trim($row[8]) : '';
                        $provinceValue = isset($row[3]) ? trim($row[3]) : '';
                        $status = isset($row[23]) ? trim($row[23]) : '';

                        if (!empty($customerName) && !empty($productNameValue) && !empty($provinceValue) && !empty($status)) {
                            $latestOrder = [
                                'customer_name' => $customerName,
                                'product_name'  => $productNameValue,
                                'province'      => $provinceValue,
                                'status'        => $status,
                            ];
                        }
                    }
                }

                arsort($topProducts);
                arsort($topProvinces);

                $finalTopProducts = array_slice($topProducts, 0, 5, true);
                $finalTopProvinces = array_slice($topProvinces, 0, 5, true);

                $data = [
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $processedRows,
                    'latest_order' => $latestOrder,
                    'top_products' => $finalTopProducts,
                    'top_provinces' => $finalTopProvinces,
                ];
                $pusher->trigger('livestream-channel', 'livestream-update', $data);

                $this->info('Successfully pushed update. Total Revenue: ' . number_format($totalRevenue));

            } catch (\Exception $e) {
                $this->error("An error occurred during the loop: " . $e->getMessage());
                $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
                $this->info("Waiting for 30 seconds before retrying after error...");
                sleep(30);
                continue;
            }

            $cycleDuration = microtime(true) - $startTime;
            $this->info(sprintf('Cycle finished in %.2f seconds.', $cycleDuration));

            $sleepDuration = 20 - $cycleDuration;

            if ($sleepDuration > 0) {
                $this->info(sprintf('Waiting for %.2f seconds...', $sleepDuration));
                usleep($sleepDuration * 1000000);
            }
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
        $string = str_replace('.', '', $string);
        $string = str_replace(',', '', $string);
        return (float)filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
