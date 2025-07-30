<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Añadir conteo de items a cada orden
        $orders->getCollection()->transform(function ($order) {
            $order->items_count = $order->items->count();
            return $order;
        });

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.product']);
        $order->items_count = $order->items->count();
        
        return response()->json($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calcular subtotal
            $subtotal = 0;
            $orderItems = [];
            
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                // Verificar stock
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                }
                
                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $itemTotal,
                ];
                
                // Reducir stock
                $product->decrement('stock', $item['quantity']);
            }
            
            // Calcular IVA (21%)
            $tax = $subtotal * 0.21;
            $total = $subtotal + $tax + $validated['shipping'];
            
            // Generar número de orden
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $orderNumber = 'ORD-' . str_pad(($lastOrder ? $lastOrder->id + 1 : 1), 6, '0', STR_PAD_LEFT);
            
            // Crear orden
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $validated['customer_id'],
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $validated['shipping'],
                'total' => $total,
            ]);
            
            // Crear items de la orden
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }
            
            // Actualizar total_spent del cliente
            $customer = Customer::find($validated['customer_id']);
            $customer->increment('total_spent', $total);
            $customer->update(['last_order_at' => now()]);
            
            DB::commit();
            
            $order->load(['customer', 'items.product']);
            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // Actualizar el estado
        $order->status = $newStatus;

        // Actualizar fechas según el estado
        if ($newStatus === 'shipped' && !$order->shipped_at) {
            $order->shipped_at = now();
        }
        
        if ($newStatus === 'delivered' && !$order->delivered_at) {
            $order->delivered_at = now();
            
            // Actualizar la fecha del último pedido del cliente
            $order->customer->update([
                'last_order_at' => now()
            ]);
        }

        // Si se cancela el pedido, ajustar el total_spent del cliente y devolver stock
        if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
            $order->customer->decrement('total_spent', $order->total);
            
            // Devolver stock de productos
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
        } elseif ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
            $order->customer->increment('total_spent', $order->total);
            
            // Reducir stock de productos
            foreach ($order->items as $item) {
                $item->product->decrement('stock', $item->quantity);
            }
        }

        $order->save();
        
        // Recargar las relaciones para la respuesta
        $order->load(['customer', 'items.product']);

        return response()->json($order);
    }

public function destroy(Order $order)
{
    // Solo permitir eliminar órdenes canceladas
    if ($order->status !== 'cancelled') {
        return response()->json([
            'message' => 'Solo se pueden eliminar pedidos cancelados'
        ], 422);
    }

    $order->delete();

    return response()->json([
        'message' => 'Pedido eliminado correctamente'
    ]);
}
}