<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ward; // Assuming this is your Ward model
use App\Models\District;
use Illuminate\Support\Facades\DB;

class WardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = fopen(database_path('data/vietnam_administrative_units.csv'), 'r');
        $firstline = true;
        $wards = [];
        // No need for a map if using district_code directly
        // $districtCodeMap = District::pluck('id', 'code')->all();

        if (($handle = $csvFile) !== FALSE) {
            while (($data = fgetcsv($handle, 2000, "\t")) !== FALSE) {
                if (!$firstline) {
                    $districtCode = $data[3]; // Ward is linked by district code
                    $wardCode = $data[4]; // Ward Code is at index 4
                    $wardName = $data[5]; // Ward Name is at index 5

                    // Use only district_code and wardCode for the uniqueness key, matching the DB constraint
                    $key = $districtCode . '-' . $wardCode;

                    // Check if this district_code + wardCode combination already processed
                    if (!empty($districtCode) && !empty($wardCode) && !isset($wards[$key])) {
                        $wards[$key] = [
                            'name' => $wardName, // Use corrected $wardName
                            'code' => $wardCode, // Use corrected $wardCode
                            'district_code' => $districtCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                $firstline = false;
            }
            fclose($handle);
        }

        // Insert unique wards into the database
        if (!empty($wards)) {
            // Get all unique district codes from our collected wards
            $districtCodesInWards = array_unique(array_column($wards, 'district_code'));

            // Check which of these district codes actually exist in the districts table
            $existingDistrictCodes = District::whereIn('code', $districtCodesInWards)->pluck('code')->all();
            $existingDistrictCodesSet = array_flip($existingDistrictCodes); // Faster lookups

            // Filter the original $wards array, keeping only those whose district_code exists
            $filteredWards = array_filter($wards, function($ward) use ($existingDistrictCodesSet) {
                return isset($existingDistrictCodesSet[$ward['district_code']]);
            });

            // Now insert the *values* of the filtered associative array
            if (!empty($filteredWards)) {
                Ward::insert(array_values($filteredWards));
            }
        }
    }
}
