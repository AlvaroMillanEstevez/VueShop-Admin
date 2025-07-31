<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            
            Log::info('Loading stats for user', ['user_id' => $userId, 'is_admin' => $isAdmin]);
            
            // Calcular estadísticas del mes actual y anterior
            $currentMonth = now()->startOfMonth();
            $previousMonth = now()->subMonth()->startOfMonth();
            
            // Ingresos totales
            $currentRevenue = Order::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->where('created_at', '>=', $currentMonth)
                ->sum('total') ?? 0;
                
            $previousRevenue = Order::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereBetween('created_at', [$previousMonth, $currentMonth])
                ->sum('total') ?? 0;
            
            // Pedidos totales
            $currentOrders = Order::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->where('created_at', '>=', $currentMonth)
                ->count();
                
            $previousOrders = Order::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereBetween('created_at', [$previousMonth, $currentMonth])
                ->count();
            
            // Clientes activos
            $activeCustomers = Customer::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereHas('orders', function ($query) {
                    $query->where('created_at', '>=', now()->subDays(30));
                })
                ->count();
            
            // Productos en stock
            $productsInStock = Product::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->where('active', true)
                ->where('stock', '>', 0)
                ->count();

            $stats = [
                'total_revenue' => [
                    'current' => (float)$currentRevenue,
                    'previous' => (float)$previousRevenue,
                    'growth' => $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0
                ],
                'total_orders' => [
                    'current' => $currentOrders,
                    'previous' => $previousOrders
                ],
                'active_customers' => $activeCustomers,
                'products_in_stock' => $productsInStock,
            ];

            Log::info('Stats calculated', $stats);
            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Error in stats:', ['error' => $e->getMessage()]);
            return response()->json([
                'total_revenue' => ['current' => 0, 'previous' => 0, 'growth' => 0],
                'total_orders' => ['current' => 0, 'previous' => 0],
                'active_customers' => 0,
                'products_in_stock' => 0,
            ]);
        }
    }

    /**
     * Get sales chart data
     */
    public function salesChart(Request $request)
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            $days = $request->get('days', 30);
            
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            
            $salesData = Order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('COALESCE(SUM(total), 0) as revenue')
                )
                ->when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Rellenar días faltantes con 0
            $chartData = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $dayData = $salesData->firstWhere('date', $date);
                
                $chartData[] = [
                    'date' => $date,
                    'orders' => $dayData ? (int)$dayData->orders : 0,
                    'revenue' => $dayData ? (float)$dayData->revenue : 0
                ];
            }

            return response()->json($chartData);

        } catch (\Exception $e) {
            Log::error('Error in salesChart:', ['error' => $e->getMessage()]);
            return response()->json([]);
        }
    }

    /**
     * Get top products
     */
    public function topProducts()
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            
            // Consulta simplificada sin joins complejos
            $topProducts = Product::when(!$isAdmin, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->where('active', true)
                ->orderBy('stock', 'desc') // Por ahora ordenar por stock
                ->limit(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->category,
                        'total_sold' => rand(5, 50), // Datos temporales
                        'revenue' => $product->price * rand(5, 50),
                        'image_url' => $product->image_url,
                    ];
                });

            return response()->json($topProducts);

        } catch (\Exception $e) {
            Log::error('Error in topProducts:', ['error' => $e->getMessage()]);
            return response()->json([]);
        }
    }

    /**
     * Get recent orders
     */
    public function recentOrders()
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->role === 'admin';
            
            $query = Order::with(['customer', 'user']); // Incluir relación con user (vendedor)
            
            if (!$isAdmin) {
                // Manager: solo sus pedidos
                $query->where('user_id', $userId);
            }
            // Admin: ve todos los pedidos de todos los managers
            
            $recentOrders = $query->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($order) use ($isAdmin) {
                    $data = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer->name ?? 'Cliente eliminado',
                        'status' => $order->status,
                        'total' => (float)$order->total,
                        'date' => $order->created_at->format('Y-m-d H:i:s')
                    ];
                    
                    // Solo incluir vendedor si es admin
                    if ($isAdmin) {
                        $data['seller_name'] = $order->user->name ?? 'Sin asignar';
                        $data['seller_id'] = $order->user_id;
                    }
                    
                    return $data;
                });

            return response()->json($recentOrders);

        } catch (\Exception $e) {
            Log::error('Error in recentOrders:', ['error' => $e->getMessage()]);
            return response()->json([]);
        }
    }

    /**
     * Test endpoint
     */
    public function test()
    {
        return response()->json([
            'message' => 'VueShop Admin API is working!',
            'timestamp' => now(),
            'user' => Auth::user(),
            'role' => Auth::user()->role,
        ]);
    }
}