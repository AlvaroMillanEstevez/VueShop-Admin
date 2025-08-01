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
                ->with(['customer', 'seller', 'items']); // Added items to calculate totals
            
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
                
                // Calculate total from items if order total is 0 or null
                $calculatedTotal = $order->total;
                if (($calculatedTotal == 0 || $calculatedTotal === null) && $order->items) {
                    $calculatedTotal = $order->items->sum(function ($item) {
                        return $item->quantity * ($item->unit_price ?? $item->price ?? 0);
                    });
                }
                
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
                    'total' => (float) $calculatedTotal,
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
            $query = Order::with(['customer', 'items', 'items.product']);
            
            // Check user permissions
            $user = $request->user();
            if (!$user->is_admin) {
                $query->where('user_id', $user->id); // Using user_id instead of seller_id
            }
            
            $order = $query->findOrFail($id);
            
            // Calculate totals from items if not set
            $subtotal = $order->subtotal;
            $total = $order->total;
            
            if (($subtotal == 0 || $subtotal === null) && $order->items) {
                $subtotal = $order->items->sum(function ($item) {
                    return $item->quantity * ($item->unit_price ?? $item->price ?? 0);
                });
            }
            
            if (($total == 0 || $total === null)) {
                $tax = (float) ($order->tax ?? 0);
                $shipping = (float) ($order->shipping ?? 0);
                $total = $subtotal + $tax + $shipping;
            }
            
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
                    $unitPrice = $item->unit_price ?? $item->price ?? 0;
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product ? $item->product->name : ($item->product_name ?? 'N/A'),
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $unitPrice,
                        'total_price' => (float) ($item->total_price ?? ($item->quantity * $unitPrice)),
                    ];
                }) : [],
                'subtotal' => (float) $subtotal,
                'tax' => (float) ($order->tax ?? 0),
                'shipping' => (float) ($order->shipping ?? 0),
                'total' => (float) $total,
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
                'subtotal' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'shipping' => 'nullable|numeric|min:0',
                'total' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            // Calculate totals if not provided
            $itemsSubtotal = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });
            
            $subtotal = $request->subtotal ?? $itemsSubtotal;
            $tax = $request->tax ?? 0;
            $shipping = $request->shipping ?? 0;
            $total = $request->total ?? ($subtotal + $tax + $shipping);
            
            // Generate order number
            $orderNumber = 'ORD-' . date('y') . date('m') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id, // Seller ID
                'customer_id' => $request->customer_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);
            
            // Create order items
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $itemTotal,
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
    
    /**
     * Recalculate totals for existing orders (utility method)
     */
    public function recalculateTotals(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can recalculate totals'
                ], 403);
            }
            
            $orders = Order::with('items')->get();
            $updated = 0;
            
            foreach ($orders as $order) {
                if ($order->items && $order->items->count() > 0) {
                    $subtotal = $order->items->sum(function ($item) {
                        return $item->quantity * ($item->unit_price ?? $item->price ?? 0);
                    });
                    
                    $tax = (float) ($order->tax ?? 0);
                    $shipping = (float) ($order->shipping ?? 0);
                    $total = $subtotal + $tax + $shipping;
                    
                    if ($order->total != $total || $order->subtotal != $subtotal) {
                        $order->update([
                            'subtotal' => $subtotal,
                            'total' => $total
                        ]);
                        $updated++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Recalculated totals for {$updated} orders",
                'updated_count' => $updated
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error recalculating totals: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate totals',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}