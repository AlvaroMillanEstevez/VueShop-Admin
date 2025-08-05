<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "\n";
        echo "🚀 ===================================\n";
        echo "   STARTING SEEDING OF VUESHOP ADMIN\n";
        echo "   ===================================\n\n";

        $startTime = microtime(true);

        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
        ]);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        echo "\n";
        echo "✅ ===================================\n";
        echo "   SEEDING COMPLETED SUCCESSFULLY!\n";
        echo "   ===================================\n\n";

        // Show statistics
        $this->showStatistics();

        echo "\n🔑 ACCESS CREDENTIALS:\n";
        echo "   ┌─────────────────────────────────────┐\n";
        echo "   │ Admin:   admin@vueshop.com          │\n";
        echo "   │ Manager: juan@vueshop.com           │\n";
        echo "   │ Manager: maria@vueshop.com          │\n";
        echo "   │ Manager: carlos@vueshop.com         │\n";
        echo "   │ Password: password123 (for all)     │\n";
        echo "   └─────────────────────────────────────┘\n\n";

        echo "⏱️  Execution time: {$executionTime} seconds\n";
        echo "🎯 The application is ready to use!\n\n";
    }

    private function showStatistics()
    {
        $users = \App\Models\User::count();
        $customers = \App\Models\Customer::count();
        $products = \App\Models\Product::count();
        $orders = \App\Models\Order::count();
        $orderItems = \App\Models\OrderItem::count();

        // User role statistics
        $adminCount = \App\Models\User::where('role', 'admin')->count();
        $managerCount = \App\Models\User::where('role', 'manager')->count();

        // Orders by status
        $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
        $processingOrders = \App\Models\Order::where('status', 'processing')->count();
        $shippedOrders = \App\Models\Order::where('status', 'shipped')->count();
        $deliveredOrders = \App\Models\Order::where('status', 'delivered')->count();
        $cancelledOrders = \App\Models\Order::where('status', 'cancelled')->count();

        // Revenue
        $totalRevenue = \App\Models\Order::sum('total');

        echo "📊 GENERATED STATISTICS:\n";
        echo "   ┌─────────────────────────────────────┐\n";
        echo "   │ 👤 Users:                           │\n";
        echo "   │    • Total: {$users} ({$adminCount} admin, {$managerCount} managers)       │\n";
        echo "   │ 👥 Customers: {$customers}                    │\n";
        echo "   │ 📦 Products: {$products}                     │\n";
        echo "   │ 🛒 Orders: {$orders}                        │\n";
        echo "   │    • Pending: {$pendingOrders}                │\n";
        echo "   │    • Processing: {$processingOrders}             │\n";
        echo "   │    • Shipped: {$shippedOrders}                 │\n";
        echo "   │    • Delivered: {$deliveredOrders}               │\n";
        echo "   │    • Cancelled: {$cancelledOrders}               │\n";
        echo "   │ 📋 Order items: {$orderItems}                 │\n";
        echo "   │ 💰 Total revenue: €" . number_format($totalRevenue, 2) . "       │\n";
        echo "   └─────────────────────────────────────┘\n";

        echo "\n🔍 USER DISTRIBUTION:\n";
        $managers = \App\Models\User::where('role', 'manager')->get();
        foreach ($managers as $user) {
            $userCustomers = $user->customers()->count();
            $userProducts = $user->products()->count();
            $userOrders = $user->orders()->count();
            $userRevenue = $user->orders()->sum('total');

            echo "   • {$user->name}:\n";
            echo "     {$userCustomers} customers, {$userProducts} products, {$userOrders} orders (€" . number_format($userRevenue, 2) . ")\n";
        }
    }
}
