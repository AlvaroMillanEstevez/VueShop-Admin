<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        
        // Crear 15 órdenes de ejemplo
        for ($i = 1; $i <= 15; $i++) {
            $customer = $customers->random();
            $orderDate = Carbon::now()->subDays(rand(1, 30));
            
            $order = Order::create([
                'order_number' => 'ORD-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'status' => collect(['pending', 'processing', 'shipped', 'delivered'])->random(),
                'subtotal' => 0, // Calcularemos después
                'tax' => 0,
                'shipping' => rand(0, 1) ? 9.99 : 0,
                'total' => 0, // Calcularemos después
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            // Agregar 1-4 productos aleatorios a cada orden
            $numItems = rand(1, 4);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 3);
                $unitPrice = $product->price;
                $totalPrice = $unitPrice * $quantity;
                $subtotal += $totalPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }

            // Actualizar totales de la orden
            $tax = $subtotal * 0.21; // 21% IVA España
            $total = $subtotal + $tax + $order->shipping;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);
        }
    }
}
