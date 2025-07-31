<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Query only users with role 'customer' OR users who have been customers in orders
            $query = User::query()
                ->withCount('orders as customer_orders_count') // Orders where they are the customer
                ->withSum('orders as customer_total_spent', 'total');
            
            // Only get actual customers - users with customer role OR users who appear as customers in orders
            $query->where(function (Builder $q) {
                $q->where('role', 'customer')
                  ->orWhereHas('orders'); // Users who have placed orders as customers
            })
            // Exclude admin and manager roles from customer list
            ->whereNotIn('role', ['admin', 'manager']);
            
            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Check user permissions
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Non-admin users can't access customer data
            if (!$user->is_admin && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }
            
            // Paginate results
            $customers = $query->orderBy('created_at', 'desc')->paginate(15);
            
            // Transform the data to include calculated fields
            $transformedCustomers = $customers->getCollection()->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone ?? null,
                    'address' => $customer->address ?? null,
                    'role' => $customer->role,
                    'is_verified' => (bool) $customer->email_verified_at,
                    'orders_count' => $customer->customer_orders_count ?? 0,
                    'total_spent' => $customer->customer_total_spent ?? 0,
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
     * Display the specified customer.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Check permissions
            if (!$user->is_admin && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            $customer = User::with(['orders' => function ($query) {
                $query->with('items.product')
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            }])
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->findOrFail($id);
            
            // Transform customer data
            $customerData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'role' => $customer->role,
                'is_verified' => (bool) $customer->email_verified_at,
                'orders_count' => $customer->orders_count ?? 0,
                'total_spent' => $customer->orders_sum_total ?? 0,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
                'recent_orders' => $customer->orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                        'status' => $order->status,
                        'items_count' => $order->items->count(),
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
            
            // Check permissions
            if (!$user->is_admin && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'password' => 'required|string|min:8',
            ]);
            
            $customer = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'password' => bcrypt($request->password),
                'role' => 'customer',
                'is_active' => true,
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
            
            // Check permissions
            if (!$user->is_admin && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            $customer = User::findOrFail($id);
            
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $customer->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'password' => 'sometimes|string|min:8',
            ]);
            
            $updateData = $request->only(['name', 'email', 'phone', 'address']);
            
            if ($request->filled('password')) {
                $updateData['password'] = bcrypt($request->password);
            }
            
            $customer->update($updateData);
            
            return response()->json([
                'success' => true,
                'data' => $customer,
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
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Check permissions
            if (!$user->is_admin && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            
            $customer = User::findOrFail($id);
            
            // Check if customer has orders
            if ($customer->orders()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with existing orders'
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
}