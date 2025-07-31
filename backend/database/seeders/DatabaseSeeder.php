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
        echo "   INICIANDO SEEDING DE VUESHOP ADMIN\n";
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
        echo "   ¡SEEDING COMPLETADO EXITOSAMENTE!\n";
        echo "   ===================================\n\n";
        
        // Mostrar estadísticas
        $this->showStatistics();
        
        echo "\n🔑 CREDENCIALES DE ACCESO:\n";
        echo "   ┌─────────────────────────────────────┐\n";
        echo "   │ Admin:   admin@vueshop.com          │\n";
        echo "   │ Manager: juan@vueshop.com           │\n";
        echo "   │ Manager: maria@vueshop.com          │\n";
        echo "   │ Manager: carlos@vueshop.com         │\n";
        echo "   │ Password: password123 (para todos)  │\n";
        echo "   └─────────────────────────────────────┘\n\n";
        
        echo "⏱️  Tiempo de ejecución: {$executionTime} segundos\n";
        echo "🎯 La aplicación está lista para usar!\n\n";
    }

    private function showStatistics()
    {
        $users = \App\Models\User::count();
        $customers = \App\Models\Customer::count();
        $products = \App\Models\Product::count();
        $orders = \App\Models\Order::count();
        $orderItems = \App\Models\OrderItem::count();
        
        // Estadísticas por usuario
        $adminCount = \App\Models\User::where('role', 'admin')->count();
        $managerCount = \App\Models\User::where('role', 'manager')->count();
        
        // Estadísticas de pedidos por estado
        $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
        $processingOrders = \App\Models\Order::where('status', 'processing')->count();
        $shippedOrders = \App\Models\Order::where('status', 'shipped')->count();
        $deliveredOrders = \App\Models\Order::where('status', 'delivered')->count();
        $cancelledOrders = \App\Models\Order::where('status', 'cancelled')->count();
        
        // Ingresos totales
        $totalRevenue = \App\Models\Order::sum('total');
        
        echo "📊 ESTADÍSTICAS GENERADAS:\n";
        echo "   ┌─────────────────────────────────────┐\n";
        echo "   │ 👤 Usuarios:                        │\n";
        echo "   │    • Total: {$users} ({$adminCount} admin, {$managerCount} managers)       │\n";
        echo "   │ 👥 Clientes: {$customers}                      │\n";
        echo "   │ 📦 Productos: {$products}                     │\n";
        echo "   │ 🛒 Pedidos: {$orders}                       │\n";
        echo "   │    • Pendientes: {$pendingOrders}                 │\n";
        echo "   │    • Procesando: {$processingOrders}                │\n";
        echo "   │    • Enviados: {$shippedOrders}                  │\n";
        echo "   │    • Entregados: {$deliveredOrders}                │\n";
        echo "   │    • Cancelados: {$cancelledOrders}                 │\n";
        echo "   │ 📋 Items de pedidos: {$orderItems}              │\n";
        echo "   │ 💰 Ingresos totales: €" . number_format($totalRevenue, 2) . "       │\n";
        echo "   └─────────────────────────────────────┘\n";
        
        echo "\n🔍 DISTRIBUCIÓN POR USUARIO:\n";
        $managers = \App\Models\User::where('role', 'manager')->get();
        foreach ($managers as $user) {
            $userCustomers = $user->customers()->count();
            $userProducts = $user->products()->count();
            $userOrders = $user->orders()->count();
            $userRevenue = $user->orders()->sum('total');
            
            echo "   • {$user->name}:\n";
            echo "     {$userCustomers} clientes, {$userProducts} productos, {$userOrders} pedidos (€" . number_format($userRevenue, 2) . ")\n";
        }
    }
}