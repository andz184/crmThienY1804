<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\District;
use App\Models\Ward;

class LocationController extends Controller
{
    public function getDistricts(Request $request)
    {
        $provinceCode = $request->get('province_code');
        $districts = District::where('province_code', $provinceCode)
            ->pluck('name', 'code');

        return response()->json($districts);
    }

    public function getWards(Request $request)
    {
        $districtCode = $request->get('district_code');
        $wards = Ward::where('district_code', $districtCode)
            ->pluck('name', 'code');

        return response()->json($wards);
    }
}
