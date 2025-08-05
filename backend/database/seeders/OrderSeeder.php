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
        echo "\n\ud83c\udf4e Creating orders...\n";

        // ONLY MANAGERS will have orders - Admin does not have own orders
        $managers = User::where('role', 'manager')->get();
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $statusWeights = [15, 25, 30, 25, 5]; // Probabilities of each status

        // Retrieve all customers (they are global, not assigned to any user)
        $allCustomers = Customer::all();

        if ($allCustomers->isEmpty()) {
            echo "   \u26a0\ufe0f No customers found. Please run CustomerSeeder first.\n";
            return;
        }

        foreach ($managers as $user) {
            $products = Product::where('user_id', $user->id)->get();

            if ($products->isEmpty()) {
                echo "   \u26a0\ufe0f No products found for {$user->name}. Skipping...\n";
                continue;
            }

            // Create between 15-30 orders per manager
            $orderCount = rand(15, 30);

            for ($i = 1; $i <= $orderCount; $i++) {
                // Random date within the last 6 months, favoring recent orders
                $daysAgo = $this->getWeightedRandomDays();
                $orderDate = Carbon::now()->subDays($daysAgo);

                // Select status based on weighted probabilities
                $statusIndex = $this->getWeightedRandomIndex($statusWeights);
                $status = $statuses[$statusIndex];

                // Create the order first without total
                $order = Order::create([
                    'user_id' => $user->id,
                    'customer_id' => $allCustomers->random()->id,
                    'order_number' => $this->generateOrderNumber($user->id, $i),
                    'status' => $status,
                    'subtotal' => 0,
                    'tax' => 0,
                    'shipping' => 0,
                    'total' => 0,
                    'notes' => rand(0, 4) === 0 ? 'Special request from customer' : null,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                // Add 1-5 items per order
                $itemCount = $this->getWeightedItemCount();
                $usedProducts = collect();
                $orderSubtotal = 0;

                for ($j = 0; $j < $itemCount; $j++) {
                    // Avoid duplicate products in the same order
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

                    $orderSubtotal += $totalPrice;
                }

                $tax = round($orderSubtotal * 0.21, 2); // 21% VAT
                $shipping = $orderSubtotal > 50 ? 0 : 5.99;
                $orderTotal = $orderSubtotal + $tax + $shipping;

                $order->update([
                    'subtotal' => $orderSubtotal,
                    'tax' => $tax,
                    'shipping' => $shipping,
                    'total' => $orderTotal,
                ]);

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

            echo "   \u2705 {$orderCount} orders created for {$user->name}\n";
        }

        echo "   \u2139\ufe0f Admin has no own orders - can only view all orders\n";
    }

    private function generateOrderNumber($userId, $orderIndex)
    {
        return 'ORD-' . date('y') . $userId . '-' . str_pad($orderIndex, 4, '0', STR_PAD_LEFT);
    }

    private function getWeightedRandomDays()
    {
        $weights = [
            [1, 7, 40],
            [8, 30, 30],
            [31, 90, 20],
            [91, 180, 10],
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as [$min, $max, $weight]) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return rand($min, $max);
            }
        }

        return rand(1, 30);
    }

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

        return 0;
    }

    private function getWeightedItemCount()
    {
        $weights = [30, 40, 20, 10];
        $index = $this->getWeightedRandomIndex($weights);

        $itemCounts = [1, 2, 3, rand(4, 5)];
        return $itemCounts[$index];
    }
}