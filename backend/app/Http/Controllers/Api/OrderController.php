<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::query()
                ->with(['customer', 'seller']); // Load both customer and seller relationships
            
            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function (Builder $q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($customerQuery) use ($search) {
                          $customerQuery->where('name', 'like', "%{$search}%")
                                       ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                          $sellerQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }
            
            // Filter by seller if requested
            if ($request->filled('seller_id')) {
                $query->where('user_id', $request->seller_id);
            }
            
            // Check user permissions
            $user = $request->user();
            if (!$user->is_admin) {
                $query->where('user_id', $user->id); // Show only orders from this seller
            }
            
            $orders = $query->orderBy('created_at', 'desc')->paginate(15);
            
            // Transform the data
            $transformedOrders = $orders->getCollection()->map(function ($order) {
                $customer = $order->customer;
                $seller = $order->seller; // This is the user who created the order (user_id)
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $customer ? $customer->name : 'N/A',
                    'customer_email' => $customer ? $customer->email : 'N/A',
                    'customer' => $customer ? [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email
                    ] : null,
                    'seller' => $seller ? [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'email' => $seller->email
                    ] : null,
                    'items_count' => $order->items ? $order->items->count() : 0,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading orders: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load orders',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Display the specified order.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $query = Order::with(['customer', 'items']);
            
            // Check user permissions
            $user = $request->user();
            if (!$user->is_admin) {
                $query->where('user_id', $user->id); // Using user_id instead of seller_id
            }
            
            $order = $query->findOrFail($id);
            
            // Transform order data
            $orderData = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'email' => $order->customer->email
                ] : null,
                'items' => $order->items ? $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product ? $item->product->name : 'N/A',
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                    ];
                }) : [],
                'subtotal' => (float) $order->subtotal,
                'tax' => (float) $order->tax,
                'shipping' => (float) $order->shipping,
                'total' => (float) $order->total,
                'status' => $order->status,
                'notes' => $order->notes,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $orderData
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error loading order: ' . $e->getMessage(), [
                'order_id' => $id,
                'user_id' => $request->user()?->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load order',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $request->validate([
                'customer_id' => 'required|exists:users,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'shipping' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            // Generate order number
            $orderNumber = 'ORD-' . strtoupper(uniqid());
            
            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id, // Seller ID
                'customer_id' => $request->customer_id,
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'shipping' => $request->shipping ?? 0,
                'total' => $request->total,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);
            
            // Create order items
            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }
            
            // Load the order with relationships
            $order->load(['customer', 'items.product']);
            
            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order created successfully'
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating order: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Update the specified order.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $query = Order::query();
            if (!$user->is_admin) {
                $query->where('user_id', $user->id);
            }
            
            $order = $query->findOrFail($id);
            
            $request->validate([
                'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
                'notes' => 'nullable|string|max:1000',
                'shipped_at' => 'nullable|date',
                'delivered_at' => 'nullable|date',
            ]);
            
            $updateData = $request->only(['status', 'notes', 'shipped_at', 'delivered_at']);
            
            $order->update($updateData);
            
            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order updated successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating order: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Update order status.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $request->validate([
                'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            ]);
            
            $query = Order::query();
            if (!$user->is_admin) {
                $query->where('user_id', $user->id);
            }
            
            $order = $query->findOrFail($id);
            
            $order->update([
                'status' => $request->status,
                'shipped_at' => $request->status === 'shipped' ? now() : $order->shipped_at,
                'delivered_at' => $request->status === 'delivered' ? now() : $order->delivered_at,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order status updated successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating order status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Remove the specified order.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $query = Order::query();
            if (!$user->is_admin) {
                $query->where('user_id', $user->id);
            }
            
            $order = $query->findOrFail($id);
            
            // Only allow deletion of pending or cancelled orders
            if (!in_array($order->status, ['pending', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending or cancelled orders can be deleted'
                ], 400);
            }
            
            $order->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting order: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}