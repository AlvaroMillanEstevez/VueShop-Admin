<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

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
            $query->with('items.product')
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
        }]);
        
        // Añadir el conteo de órdenes
        $customer->orders_count = $customer->orders()->count();
        
        return response()->json($customer);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        // Inicializar total_spent en 0 para nuevos clientes
        $validated['total_spent'] = 0;

        $customer = Customer::create($validated);
        
        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $customer->update($validated);
        
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        // Verificar si el cliente tiene pedidos antes de eliminar
        if ($customer->orders()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un cliente con pedidos existentes'
            ], 422);
        }

        $customer->delete();
        
        return response()->json(null, 204);
    }
}