<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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
            
            // Base queries depending on user role
            if ($user->is_admin) {
                // Admin sees all data
                $ordersQuery = Order::query();
                $productsQuery = Product::query();
                $customersQuery = User::where('role', 'customer');
            } else {
                // Sellers see only their data - using user_id instead of seller_id
                $ordersQuery = Order::where('user_id', $user->id);
                $productsQuery = Product::where('seller_id', $user->id);
                $customersQuery = User::whereHas('orders', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }
            
            // Calculate stats
            $totalRevenue = $ordersQuery->where('status', '!=', 'cancelled')->sum('total');
            $totalOrders = $ordersQuery->count();
            $activeCustomers = $customersQuery->where('is_active', true)->count();
            $productsInStock = $productsQuery->where('active', true)->where('stock', '>', 0)->count();
            
            $stats = [
                'total_revenue' => [
                    'amount' => (float) $totalRevenue,
                    'currency' => 'EUR'
                ],
                'total_orders' => [
                    'count' => $totalOrders
                ],
                'active_customers' => $activeCustomers,
                'products_in_stock' => $productsInStock
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading dashboard stats: ' . $e->getMessage());
            
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
                $query->where('user_id', $user->id); // Using user_id instead of seller_id
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
                $query = Product::where('seller_id', $user->id);
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
                                    'products.seller_id',
                                    DB::raw('COALESCE(SUM(order_items.quantity), 0) as sales_count')
                                ])
                                ->groupBy('products.id', 'products.name', 'products.price', 'products.image_url', 'products.seller_id')
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
            
            // Base query depending on user role
            $query = Order::query();
            
            if (!$user->is_admin) {
                $query->where('user_id', $user->id); // Using user_id instead of seller_id
            }
            
            // Get recent orders with basic data
            $recentOrders = $query->orderBy('created_at', 'desc')
                                 ->limit($limit)
                                 ->get();
            
            // Transform data
            $transformedOrders = $recentOrders->map(function ($order) {
                // Try to get customer info if customer_id exists
                $customer = null;
                if ($order->customer_id) {
                    $customer = User::find($order->customer_id);
                }
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $customer ? $customer->name : 'N/A',
                    'customer_email' => $customer ? $customer->email : 'N/A',
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'items_count' => 0, // We'll calculate this if needed
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
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard API test successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_admin' => $user->is_admin
                ],
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