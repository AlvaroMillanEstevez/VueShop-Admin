<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        echo "🛒 Creando pedidos...\n";
        
        // SOLO MANAGERS tendrán pedidos - Admin no tiene pedidos propios
        $managers = User::where('role', 'manager')->get();
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $statusWeights = [15, 25, 30, 25, 5]; // Probabilidades de cada estado

        foreach ($managers as $user) {
            $customers = $user->customers;
            $products = $user->products;
            
            if ($customers->isEmpty() || $products->isEmpty()) {
                continue;
            }
            
            // Crear entre 15-30 pedidos por usuario manager
            $orderCount = rand(15, 30);
            
            for ($i = 1; $i <= $orderCount; $i++) {
                // Fecha aleatoria en los últimos 6 meses, con más pedidos recientes
                $daysAgo = $this->getWeightedRandomDays();
                $orderDate = Carbon::now()->subDays($daysAgo);
                
                // Seleccionar estado con probabilidades ponderadas
                $statusIndex = $this->getWeightedRandomIndex($statusWeights);
                $status = $statuses[$statusIndex];
                
                $order = Order::create([
                    'user_id' => $user->id, // Solo managers
                    'customer_id' => $customers->random()->id,
                    'order_number' => $this->generateOrderNumber($user->id, $i),
                    'status' => $status,
                    'subtotal' => 0, // Se calculará después
                    'tax' => 0,
                    'shipping' => rand(0, 3) == 0 ? rand(5, 25) : 0, // 25% tienen envío
                    'total' => 0, // Se calculará después
                    'notes' => rand(0, 4) == 0 ? 'Pedido especial del cliente' : null,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                // Agregar entre 1-5 items por pedido
                $itemCount = $this->getWeightedItemCount();
                $subtotal = 0;
                $usedProducts = collect();
                
                for ($j = 0; $j < $itemCount; $j++) {
                    // Evitar productos duplicados en el mismo pedido
                    $availableProducts = $products->whereNotIn('id', $usedProducts->pluck('id'));
                    if ($availableProducts->isEmpty()) {
                        break;
                    }
                    
                    $product = $availableProducts->random();
                    $usedProducts->push($product);
                    
                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;
                    $totalPrice = $unitPrice * $quantity;
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                    
                    $subtotal += $totalPrice;
                }
                
                // Calcular totales
                $tax = $subtotal * 0.21; // 21% IVA España
                $total = $subtotal + $tax + $order->shipping;
                
                $order->update([
                    'subtotal' => round($subtotal, 2),
                    'tax' => round($tax, 2),
                    'total' => round($total, 2),
                ]);
                
                // Actualizar fechas según el estado
                if ($status === 'shipped') {
                    $order->update(['shipped_at' => $orderDate->copy()->addDays(rand(1, 3))]);
                } elseif ($status === 'delivered') {
                    $shippedAt = $orderDate->copy()->addDays(rand(1, 3));
                    $deliveredAt = $shippedAt->copy()->addDays(rand(1, 5));
                    $order->update([
                        'shipped_at' => $shippedAt,
                        'delivered_at' => $deliveredAt,
                    ]);
                }
            }
            
            echo "   ✅ {$orderCount} pedidos para {$user->name}\n";
        }
        
        echo "   ℹ️ Admin no tiene pedidos propios - solo puede ver todos los pedidos\n";
    }

    /**
     * Generar número de pedido único por usuario
     */
    private function generateOrderNumber($userId, $orderIndex)
    {
        return 'ORD-' . date('y') . $userId . '-' . str_pad($orderIndex, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener días aleatorios con peso hacia fechas más recientes
     */
    private function getWeightedRandomDays()
    {
        // Más pedidos recientes que antiguos
        $weights = [
            [1, 7, 40],     // Última semana: 40%
            [8, 30, 30],    // Último mes: 30%
            [31, 90, 20],   // Últimos 3 meses: 20%
            [91, 180, 10],  // Últimos 6 meses: 10%
        ];
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($weights as [$min, $max, $weight]) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return rand($min, $max);
            }
        }
        
        return rand(1, 30); // Fallback
    }

    /**
     * Seleccionar índice aleatorio basado en pesos
     */
    private function getWeightedRandomIndex($weights)
    {
        $total = array_sum($weights);
        $random = rand(1, $total);
        $cumulative = 0;
        
        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $index;
            }
        }
        
        return 0; // Fallback
    }

    /**
     * Obtener cantidad de items por pedido con distribución realista
     */
    private function getWeightedItemCount()
    {
        // 1 item: 30%, 2 items: 40%, 3 items: 20%, 4+ items: 10%
        $weights = [30, 40, 20, 10];
        $index = $this->getWeightedRandomIndex($weights);
        
        // Mapear índices a cantidades
        $itemCounts = [1, 2, 3, rand(4, 5)];
        return $itemCounts[$index];
    }
}