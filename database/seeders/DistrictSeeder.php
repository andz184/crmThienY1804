<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\District; // Assuming this is your District model
use App\Models\Province;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = fopen(database_path('data/vietnam_administrative_units.csv'), 'r');
        $firstline = true;
        $districts = [];
        // No need for a map anymore if we are using the province_code directly from the CSV
        // $provinceCodeMap = Province::pluck('id', 'code')->all(); // Get province_id by province_code

        if (($handle = $csvFile) !== FALSE) {
            while (($data = fgetcsv($handle, 2000, "\t")) !== FALSE) {
                if (!$firstline) {
                    $provinceCode = $data[1];
                    $districtName = $data[2];
                    $districtCode = $data[3];

                    // Use a composite key to ensure uniqueness for districts within a province
                    $key = $provinceCode . '-' . $districtCode . '-' . $districtName;

                    // Check if district already processed, province code exists in CSV row
                    if (!empty($provinceCode) && !isset($districts[$key])) {
                        $districts[$key] = [
                            'name' => $districtName,
                            'code' => $districtCode,
                            'province_code' => $provinceCode, // Correct foreign key column name
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                $firstline = false;
            }
            fclose($handle);
        }

        // Insert unique districts into the database
        if (!empty($districts)) {
            // Check if provinces exist before inserting districts
            $existingProvinceCodes = Province::whereIn('code', array_column(array_values($districts), 'province_code'))->pluck('code')->all();
            $districtsToInsert = array_filter(array_values($districts), function($district) use ($existingProvinceCodes) {
                return in_array($district['province_code'], $existingProvinceCodes);
            });

            if (!empty($districtsToInsert)) {
                 District::insert($districtsToInsert);
            }
        }
    }
}
