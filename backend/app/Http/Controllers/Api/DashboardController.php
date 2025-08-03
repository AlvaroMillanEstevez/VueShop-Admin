<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Log para debugging
            \Log::info('Dashboard stats request', [
                'user_id' => $user->id,
                'is_admin' => $user->is_admin,
                'role' => $user->role
            ]);
            
            // Base queries depending on user role
            if ($user->is_admin) {
                // Admin sees all data
                $ordersQuery = Order::query();
                $productsQuery = Product::query();
                $customersQuery = Customer::query();
            } else {
                // Sellers see only their data
                $ordersQuery = Order::where('user_id', $user->id);
                $productsQuery = Product::where('user_id', $user->id);
                // Para customers de este vendedor específico
                $customersQuery = Customer::whereIn('id', function($query) use ($user) {
                    $query->select('customer_id')
                          ->from('orders')
                          ->where('user_id', $user->id)
                          ->whereNotNull('customer_id')
                          ->distinct();
                });
            }
            
            // Calculate stats
            $totalRevenue = $ordersQuery->where('status', '!=', 'cancelled')->sum('total');
            $totalOrders = $ordersQuery->count();
            $activeCustomers = $customersQuery->count(); // Customers que han hecho pedidos
            $productsInStock = $productsQuery->where('active', true)->where('stock', '>', 0)->count();
            
            // Log de los valores calculados
            \Log::info('Stats calculated', [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'active_customers' => $activeCustomers,
                'products_in_stock' => $productsInStock
            ]);
            
            $stats = [
                'total_revenue' => [
                    'amount' => (float) $totalRevenue,
                    'currency' => 'EUR'
                ],
                'total_orders' => [
                    'count' => (int) $totalOrders
                ],
                'active_customers' => (int) $activeCustomers,
                'products_in_stock' => (int) $productsInStock
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading dashboard stats: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get sales chart data
     */
    public function salesChart(Request $request): JsonResponse
    {
        try {
            $days = (int) $request->get('days', 30);
            $user = $request->user();
            
            // Validate days parameter
            if ($days < 1 || $days > 365) {
                $days = 30;
            }
            
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            
            // Base query depending on user role
            $query = Order::whereBetween('created_at', [$startDate, $endDate])
                          ->where('status', '!=', 'cancelled');
            
            if (!$user->is_admin) {
                $query->where('user_id', $user->id);
            }
            
            // Get daily sales data
            $salesData = $query->selectRaw('DATE(created_at) as date, SUM(total) as amount')
                              ->groupBy(DB::raw('DATE(created_at)'))
                              ->orderBy('date')
                              ->get();
            
            // Create array with all dates (fill missing dates with 0)
            $chartData = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                $dateString = $currentDate->toDateString();
                $found = $salesData->firstWhere('date', $dateString);
                
                $chartData[] = [
                    'date' => $dateString,
                    'amount' => $found ? (float) $found->amount : 0.0
                ];
                
                $currentDate->addDay();
            }
            
            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading sales chart: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load sales chart data',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get top products
     */
    public function topProducts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = (int) $request->get('limit', 5);
            
            // Base query depending on user role
            if ($user->is_admin) {
                $query = Product::query();
            } else {
                $query = Product::where('user_id', $user->id);
            }
            
            // Get products with sales count from order items
            $topProducts = $query->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                                ->leftJoin('orders', function($join) {
                                    $join->on('order_items.order_id', '=', 'orders.id')
                                         ->where('orders.status', '!=', 'cancelled');
                                })
                                ->select([
                                    'products.id',
                                    'products.name',
                                    'products.price',
                                    'products.image_url',
                                    'products.user_id',
                                    DB::raw('COALESCE(SUM(order_items.quantity), 0) as sales_count')
                                ])
                                ->groupBy('products.id', 'products.name', 'products.price', 'products.image_url', 'products.user_id')
                                ->orderBy('sales_count', 'desc')
                                ->limit($limit)
                                ->get();
            
            // Transform data
            $transformedProducts = $topProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'image_url' => $product->image_url,
                    'sales_count' => (int) $product->sales_count,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedProducts
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading top products: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load top products',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get recent orders
     */
    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = (int) $request->get('limit', 10);
            
            // Eager load con las relaciones correctas
            $query = Order::with(['customer', 'seller']);
            
            if (!$user->is_admin) {
                $query->where('user_id', $user->id);
            }
            
            // Get recent orders with customer and seller data
            $recentOrders = $query->orderBy('created_at', 'desc')
                                 ->limit($limit)
                                 ->get();
            
            // Transform data
            $transformedOrders = $recentOrders->map(function ($order) {
                // Obtener customer info del modelo Customer
                $customerName = 'N/A';
                $customerEmail = 'N/A';
                
                if ($order->customer) {
                    $customerName = $order->customer->name;
                    $customerEmail = $order->customer->email;
                }
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'seller_name' => $order->seller ? $order->seller->name : 'N/A',
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'items_count' => $order->items ? $order->items->count() : 0,
                    'created_at' => $order->created_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedOrders
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading recent orders: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent orders',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Test endpoint for debugging
     */
    public function test(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Add some debug info about database structure
            $debugInfo = [
                'products_count' => Product::count(),
                'orders_count' => Order::count(),
                'users_count' => User::count(),
                'customers_count' => Customer::count(),
            ];
            
            if (!$user->is_admin) {
                $debugInfo['user_products'] = Product::where('user_id', $user->id)->count();
                $debugInfo['user_orders'] = Order::where('user_id', $user->id)->count();
                $debugInfo['user_products_active'] = Product::where('user_id', $user->id)->where('active', true)->count();
                $debugInfo['user_products_in_stock'] = Product::where('user_id', $user->id)->where('active', true)->where('stock', '>', 0)->count();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard API test successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_admin' => $user->is_admin
                ],
                'debug' => $debugInfo,
                'timestamp' => now(),
                'environment' => app()->environment()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dashboard API test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}