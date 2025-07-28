<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        // Estadísticas principales
        $thisMonth = Order::thisMonth()->sum('total');
        $lastMonth = Order::lastMonth()->sum('total');
        $growth = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;

        $stats = [
            'total_revenue' => [
                'current' => $thisMonth,
                'previous' => $lastMonth,
                'growth' => round($growth, 1)
            ],
            'total_orders' => [
                'current' => Order::thisMonth()->count(),
                'previous' => Order::lastMonth()->count(),
            ],
            'total_customers' => Customer::count(),
            'low_stock_products' => Product::lowStock()->count(),
        ];

        return response()->json($stats);
    }

    public function salesChart()
    {
        // Datos para gráfico de ventas últimos 30 días
        $salesData = Order::dailySales(30)->get();
        
        // Rellenar días sin ventas con 0
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayData = $salesData->firstWhere('date', $date);
            
            $chartData[] = [
                'date' => $date,
                'orders' => $dayData ? $dayData->orders : 0,
                'revenue' => $dayData ? floatval($dayData->revenue) : 0,
            ];
        }

        return response()->json($chartData);
    }

    public function topProducts()
    {
        $topProducts = Product::topSelling(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'total_sold' => $product->total_sold ?? 0,
                    'revenue' => $product->price * ($product->total_sold ?? 0),
                    'image_url' => $product->image_url,
                ];
            });

        return response()->json($topProducts);
    }

    public function recentOrders()
    {
        $recentOrders = Order::with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer->name,
                    'status' => $order->status,
                    'total' => $order->total,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json($recentOrders);
    }
}
