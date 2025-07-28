<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('orders')
            ->orderBy('total_spent', 'desc')
            ->paginate(15);

        return response()->json($customers);
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);
        
        return response()->json($customer);
    }
}