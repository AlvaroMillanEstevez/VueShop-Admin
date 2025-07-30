<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        // Calcular ingresos del mes actual
        $thisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        // Calcular ingresos del mes anterior
        $lastMonth = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        // Calcular el crecimiento
        $growth = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;

        // Contar órdenes del mes actual y anterior
        $ordersThisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', 'cancelled')
            ->count();

        $ordersLastMonth = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->where('status', '!=', 'cancelled')
            ->count();

        $stats = [
            'total_revenue' => [
                'current' => round($thisMonth, 2),
                'previous' => round($lastMonth, 2),
                'growth' => round($growth, 1)
            ],
            'total_orders' => [
                'current' => $ordersThisMonth,
                'previous' => $ordersLastMonth,
            ],
            'total_customers' => Customer::count(),
            'low_stock_products' => Product::where('stock', '<=', 10)
                ->where('active', true)
                ->count(),
        ];

        return response()->json($stats);
    }

    public function salesChart(Request $request)
    {
        // Obtener el período de días del request (por defecto 30)
        $days = $request->get('days', 30);
        
        // Obtener ventas del período especificado
        $salesData = Order::where('created_at', '>=', now()->subDays($days))
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
        
        // Crear un array con todos los días (incluyendo los que no tienen ventas)
        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayData = $salesData->get($date);
            
            $chartData[] = [
                'date' => $date,
                'orders' => $dayData ? $dayData->orders : 0,
                'revenue' => $dayData ? round(floatval($dayData->revenue), 2) : 0,
            ];
        }

        return response()->json($chartData);
    }

    public function topProducts()
    {
        // Usar una subconsulta para evitar problemas con GROUP BY
        $topProducts = Product::select([
                'products.id',
                'products.name',
                'products.category',
                'products.image_url'
            ])
            ->selectRaw('(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.product_id = products.id) as total_sold')
            ->selectRaw('(SELECT COALESCE(SUM(oi.total_price), 0) FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = products.id AND o.status != "cancelled") as revenue')
            ->where('products.active', true)
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'total_sold' => intval($product->total_sold),
                    'revenue' => round(floatval($product->revenue), 2),
                    'image_url' => $product->image_url ?? 'https://via.placeholder.com/150',
                ];
            });

        return response()->json($topProducts);
    }

    public function recentOrders()
    {
        $recentOrders = Order::with(['customer', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer->name ?? 'Cliente eliminado',
                    'status' => $order->status,
                    'total' => round($order->total, 2),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->toISOString(),
                ];
            });

        return response()->json($recentOrders);
    }
}