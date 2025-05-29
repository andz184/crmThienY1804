<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('query');

        $customers = Customer::where(function($q) use ($query) {
            $q->where('phone', 'LIKE', "%{$query}%")
              ->orWhere('name', 'LIKE', "%{$query}%")
              ->orWhere('email', 'LIKE', "%{$query}%");
        })
        ->select('id', 'name', 'phone', 'email', 'street_address', 'province_code', 'district_code', 'ward_code', 'purchase_count', 'total_spent', 'avatar')
        ->limit(10)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
}
