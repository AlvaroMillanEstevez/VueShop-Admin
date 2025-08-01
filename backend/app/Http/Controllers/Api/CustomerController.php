<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     * Todos los usuarios autenticados pueden ver todos los clientes.
     * Los clientes son entidades independientes, no pertenecen a usuarios específicos.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Verificar que el usuario tenga un rol válido
            if (!in_array($user->role, ['admin', 'manager', 'seller'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Insufficient privileges.'
                ], 403);
            }

            // Query base - todos los clientes con sus estadísticas de pedidos
            $query = Customer::query()
                ->withCount('orders')
                ->withSum('orders', 'total');

            // Aplicar búsqueda si se proporciona
            if ($request->filled('search')) {
                $search = $request->search;
                $query->search($search);
            }

            // Filtros adicionales opcionales
            if ($request->filled('has_orders')) {
                if ($request->has_orders === 'true') {
                    $query->withOrders();
                } elseif ($request->has_orders === 'false') {
                    $query->withoutOrders();
                }
            }

            // Ordenar por fecha de creación (más recientes primero)
            $query->orderBy('created_at', 'desc');

            // Paginar resultados
            $customers = $query->paginate(15);

            // Transformar los datos
            $transformedCustomers = $customers->getCollection()->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone ?? null,
                    'address' => $customer->address ?? null,
                    'city' => $customer->city ?? null,
                    'country' => $customer->country ?? null,
                    'notes' => $customer->notes ?? null,
                    'orders_count' => $customer->orders_count ?? 0,
                    'total_spent' => $customer->orders_sum_total ?? 0,
                    'last_order_at' => $customer->last_order_at,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedCustomers,
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading customers: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customers',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Display the specified customer with recent orders.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Verificar permisos básicos
            if (!in_array($user->role, ['admin', 'manager', 'seller'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            // Obtener cliente con sus pedidos recientes
            $customer = Customer::with(['orders' => function ($query) {
                $query->with(['items.product', 'seller']) // Incluir vendedor del pedido
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            }])
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->findOrFail($id);
            
            // Transformar datos del cliente
            $customerData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'country' => $customer->country,
                'notes' => $customer->notes,
                'orders_count' => $customer->orders_count ?? 0,
                'total_spent' => $customer->orders_sum_total ?? 0,
                'last_order_at' => $customer->last_order_at,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
                'recent_orders' => $customer->orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                        'status' => $order->status,
                        'items_count' => $order->items->count(),
                        'seller_name' => $order->seller->name ?? 'Unknown',
                        'created_at' => $order->created_at,
                    ];
                })
            ];
            
            return response()->json([
                'success' => true,
                'data' => $customerData
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error loading customer: ' . $e->getMessage(), [
                'customer_id' => $id,
                'user_id' => $request->user()?->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customer',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Verificar permisos
            if (!in_array($user->role, ['admin', 'manager', 'seller'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            // Los clientes son independientes, no se asignan a usuarios
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'country' => $request->country ?? 'Spain',
                'notes' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Customer created successfully'
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating customer: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Verificar permisos
            if (!in_array($user->role, ['admin', 'manager', 'seller'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            $customer = Customer::findOrFail($id);
            
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            $customer->update($request->only([
                'name', 'email', 'phone', 'address', 'city', 'country', 'notes'
            ]));
            
            return response()->json([
                'success' => true,
                'data' => $customer->fresh(),
                'message' => 'Customer updated successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating customer: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Remove the specified customer.
     * Solo se permite si no tiene pedidos asociados.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Verificar permisos - solo admin puede eliminar clientes
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only administrators can delete customers.'
                ], 403);
            }
            
            $customer = Customer::findOrFail($id);
            
            // Verificar si tiene pedidos
            if ($customer->orders()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with existing orders. Customer has ' . $customer->orders()->count() . ' orders.'
                ], 400);
            }
            
            $customer->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Error deleting customer: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get customer statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!in_array($user->role, ['admin', 'manager', 'seller'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $stats = [
                'total_customers' => Customer::count(),
                'customers_with_orders' => Customer::withOrders()->count(),
                'customers_without_orders' => Customer::withoutOrders()->count(),
                'total_spent_all_customers' => Customer::join('orders', 'customers.id', '=', 'orders.customer_id')
                    ->sum('orders.total'),
                'average_spent_per_customer' => Customer::withOrders()
                    ->join('orders', 'customers.id', '=', 'orders.customer_id')
                    ->selectRaw('AVG(order_totals.total) as avg_spent')
                    ->from(Customer::withOrders()
                        ->join('orders', 'customers.id', '=', 'orders.customer_id')
                        ->selectRaw('customers.id, SUM(orders.total) as total')
                        ->groupBy('customers.id'), 'order_totals')
                    ->value('avg_spent') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading customer stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customer statistics',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}