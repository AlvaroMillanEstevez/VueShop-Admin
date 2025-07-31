<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            
            $query = Order::with(['customer', 'user', 'items.product']);
            
            if (!$isAdmin) {
                // Manager: solo sus pedidos
                $query->where('user_id', $userId);
            }
            // Admin: ve todos los pedidos
            
            // Filtros
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }
            
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($customerQuery) use ($search) {
                          $customerQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }
            
            if ($request->has('seller_id') && $isAdmin) {
                $query->where('user_id', $request->get('seller_id'));
            }
            
            $orders = $query->orderByDesc('created_at')
                           ->paginate(15);
            
            // Transformar datos para incluir info del vendedor si es admin
            $orders->getCollection()->transform(function ($order) use ($isAdmin) {
                $data = [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer->name ?? 'Cliente eliminado',
                    'customer_email' => $order->customer->email ?? '',
                    'status' => $order->status,
                    'subtotal' => (float)$order->subtotal,
                    'tax' => (float)$order->tax,
                    'shipping' => (float)$order->shipping,
                    'total' => (float)$order->total,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                ];
                
                // Incluir info del vendedor si es admin
                if ($isAdmin) {
                    $data['seller'] = [
                        'id' => $order->user->id ?? null,
                        'name' => $order->user->name ?? 'Sin asignar',
                        'email' => $order->user->email ?? '',
                    ];
                }
                
                return $data;
            });
            
            return response()->json($orders);
            
        } catch (\Exception $e) {
            Log::error('Error in orders index:', ['error' => $e->getMessage()]);
            return response()->json([
                'data' => [],
                'total' => 0,
                'message' => 'Error loading orders'
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            
            // Verificar permisos
            if (!$isAdmin && $order->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $order->load(['customer', 'user', 'items.product']);
            
            $data = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->customer,
                'status' => $order->status,
                'subtotal' => (float)$order->subtotal,
                'tax' => (float)$order->tax,
                'shipping' => (float)$order->shipping,
                'total' => (float)$order->total,
                'notes' => $order->notes,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name ?? 'Producto eliminado',
                        'quantity' => $item->quantity,
                        'unit_price' => (float)$item->unit_price,
                        'total_price' => (float)$item->total_price,
                    ];
                }),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ];
            
            if ($isAdmin) {
                $data['seller'] = [
                    'id' => $order->user->id ?? null,
                    'name' => $order->user->name ?? 'Sin asignar',
                    'email' => $order->user->email ?? '',
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in order show:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading order'
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            
            // Verificar permisos
            if (!$isAdmin && $order->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($request->status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 422);
            }
            
            $order->update(['status' => $request->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating order status:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status'
            ], 500);
        }
    }
}