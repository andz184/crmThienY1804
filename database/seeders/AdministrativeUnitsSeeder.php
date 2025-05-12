<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdministrativeUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        setlocale(LC_ALL, 'en_US.UTF-8'); // Attempt to set locale for UTF-8 CSV reading

        // Disable foreign key checks for faster insertion
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Recommended: Clear existing data if you intend to fully replace it
        // DB::table('wards')->truncate();
        // DB::table('districts')->truncate();
        // DB::table('provinces')->truncate();

        $csvPath = database_path('data/vietnam_administrative_units.csv'); // ADJUST PATH IF NEEDED
        $now = Carbon::now();
        $allProcessedProvinces = [];

        if (!file_exists($csvPath) || !is_readable($csvPath)) {
            $this->command->error("CSV file not found or not readable at: {$csvPath}");
            Log::error("Seeding administrative units failed: CSV file not found or not readable at {$csvPath}");
            return;
        }

        $this->command->info("Starting seeding administrative units from: {$csvPath}");
        Log::info("Starting seeding administrative units from: {$csvPath}");

        $header = true;
        $provincesInserted = [];
        $districtsInserted = [];
        $wardsBatch = [];
        $districtsBatch = [];
        $provincesBatch = [];
        $batchSize = 500; // Adjust batch size based on performance
        $rowCount = 0;

        if (($handle = fopen($csvPath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if ($header) {
                    $header = false;
                    continue;
                }

                $rowCount++;

                // Column mapping based on the sheet: B, C, D, E, F, G
                // Adjust indices if your CSV export is different
                $provinceName = trim($row[0] ?? '');
                $provinceCode = trim($row[1] ?? '');
                $districtName = trim($row[2] ?? '');
                $districtCode = trim($row[3] ?? '');
                $wardName = trim($row[4] ?? '');
                $wardCode = trim($row[5] ?? '');

                // Attempt to convert to UTF-8
                $provinceName = mb_convert_encoding($provinceName, 'UTF-8', 'auto');
                $provinceCode = mb_convert_encoding($provinceCode, 'UTF-8', 'auto');
                $districtName = mb_convert_encoding($districtName, 'UTF-8', 'auto');
                $districtCode = mb_convert_encoding($districtCode, 'UTF-8', 'auto');
                $wardName = mb_convert_encoding($wardName, 'UTF-8', 'auto');
                $wardCode = mb_convert_encoding($wardCode, 'UTF-8', 'auto');

                // Basic validation
                if (empty($provinceCode) || empty($districtCode) || empty($wardCode) || empty($provinceName) || empty($districtName) || empty($wardName)) {
                     Log::warning("Skipping row {$rowCount} due to missing data: " . implode(',', $row));
                    continue;
                }

                if (!empty($provinceCode) && !empty($provinceName)) {
                    $allProcessedProvinces[$provinceCode] = $provinceName;
                }

                // // Prepare Province data (use updateOrInsert for safety on re-runs)
                // if (!isset($provincesInserted[$provinceCode])) {
                //     Log::info("Attempting to insert/update province: Code = '{$provinceCode}', Name = '{$provinceName}'");
                //      DB::table('provinces')->updateOrInsert(
                //          ['code' => $provinceCode],
                //          ['name' => $provinceName, 'created_at' => $now, 'updated_at' => $now]
                //      );
                //     $provincesInserted[$provinceCode] = true;
                //     // Add to batch if needed (updateOrInsert is less batch-friendly)
                //     // $provincesBatch[$provinceCode] = ['code' => $provinceCode, 'name' => $provinceName, 'created_at' => $now, 'updated_at' => $now];
                //  }

                // Prepare District data
                if (!isset($districtsInserted[$districtCode])) {
                     DB::table('districts')->updateOrInsert(
                         ['code' => $districtCode],
                         ['name' => $districtName, 'province_code' => $provinceCode, 'created_at' => $now, 'updated_at' => $now]
                     );
                     $districtsInserted[$districtCode] = true;
                     // Add to batch if needed
                     // $districtsBatch[$districtCode] = ['code' => $districtCode, 'name' => $districtName, 'province_code' => $provinceCode, 'created_at' => $now, 'updated_at' => $now];
                 }

                // Prepare Ward data (Batching is more suitable here)
                $wardsBatch[] = [
                    'code' => $wardCode,
                    'name' => $wardName,
                    'district_code' => $districtCode,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                // Insert wards batch if size limit reached
                 if (count($wardsBatch) >= $batchSize) {
                    DB::table('wards')->upsert($wardsBatch, ['code'], ['name', 'district_code', 'updated_at']); // Use upsert for efficiency
                    $wardsBatch = [];
                    $this->command->info("Processed {$rowCount} rows...");
                }
            }
            fclose($handle);
        }

        // Insert any remaining wards
        if (!empty($wardsBatch)) {
             DB::table('wards')->upsert($wardsBatch, ['code'], ['name', 'district_code', 'updated_at']);
        }

        // Re-enable foreign key checks if disabled earlier
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Log::info("All unique provinces encountered during seeding: " . print_r($allProcessedProvinces, true));
         $this->command->info("Finished seeding administrative units. Total rows processed from CSV: {$rowCount}");
         Log::info("Finished seeding administrative units. Total rows processed from CSV: {$rowCount}");
    }
}
