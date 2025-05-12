<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Province;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = fopen(database_path('data/vietnam_administrative_units.csv'), 'r');
        $firstline = true;
        $provinces = [];

        while (($data = fgetcsv($csvFile, 2000, "\t")) !== FALSE) {
            if (!$firstline) {
                $provinceName = $data[0];
                $provinceCode = $data[1];

                // Use a composite key to ensure uniqueness
                $key = $provinceCode . '-' . $provinceName;

                if (!isset($provinces[$key])) {
                    $provinces[$key] = [
                        'name' => $provinceName,
                        'code' => $provinceCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            $firstline = false;
        }

        fclose($csvFile);

        // Insert unique provinces into the database
        if (!empty($provinces)) {
            Province::insert(array_values($provinces));
        }
    }
}
