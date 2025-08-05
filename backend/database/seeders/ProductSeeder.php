<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n\ud83d\udce6 Creating products...\n";

        $managers = User::where('role', 'manager')->get();

        $productTemplates = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'The latest iPhone with A17 Pro chip and advanced camera',
                'price' => 1199.99,
                'stock' => 25,
                'category' => 'Smartphones',
                'image_url' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=400'
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'description' => 'Premium Android smartphone with integrated AI',
                'price' => 899.99,
                'stock' => 30,
                'category' => 'Smartphones',
                'image_url' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400'
            ],
            [
                'name' => 'MacBook Air M3',
                'description' => 'Ultralight laptop with M3 chip and 8GB RAM',
                'price' => 1299.99,
                'stock' => 15,
                'category' => 'Laptops',
                'image_url' => 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=400'
            ],
            [
                'name' => 'Dell XPS 13',
                'description' => 'Premium laptop with Intel i7 processor',
                'price' => 1199.99,
                'stock' => 12,
                'category' => 'Laptops',
                'image_url' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400'
            ],
            [
                'name' => 'iPad Pro 12.9"',
                'description' => 'Professional tablet with M2 chip and Liquid Retina display',
                'price' => 1099.99,
                'stock' => 20,
                'category' => 'Tablets',
                'image_url' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400'
            ],
            [
                'name' => 'AirPods Pro 2',
                'description' => 'Wireless earbuds with noise cancellation',
                'price' => 249.99,
                'stock' => 50,
                'category' => 'Audio',
                'image_url' => 'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=400'
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'description' => 'Over-ear headphones with best-in-class noise cancellation',
                'price' => 399.99,
                'stock' => 35,
                'category' => 'Audio',
                'image_url' => 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400'
            ],
            [
                'name' => 'Apple Watch Series 9',
                'description' => 'Smartwatch with GPS and advanced health monitoring',
                'price' => 429.99,
                'stock' => 40,
                'category' => 'Wearables',
                'image_url' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=400'
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'description' => 'Hybrid console with 7-inch OLED display',
                'price' => 349.99,
                'stock' => 25,
                'category' => 'Gaming',
                'image_url' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400'
            ],
            [
                'name' => 'PlayStation 5',
                'description' => 'Next-gen console with ultra-fast SSD',
                'price' => 499.99,
                'stock' => 8,
                'category' => 'Gaming',
                'image_url' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=400'
            ],
            [
                'name' => 'Nike Air Max 270',
                'description' => 'Sports shoes with Air Max cushioning',
                'price' => 159.99,
                'stock' => 45,
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400'
            ],
            [
                'name' => 'Adidas Ultraboost 22',
                'description' => 'Premium running shoes with boost technology',
                'price' => 189.99,
                'stock' => 35,
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'
            ]
        ];

        foreach ($managers as $user) {
            // Each manager will have a random selection of 6-9 products
            $userProducts = collect($productTemplates)->shuffle()->take(rand(6, 9));

            foreach ($userProducts as $index => $template) {
                Product::create([
                    'user_id' => $user->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'price' => round($template['price'] + rand(-50, 50), 2),
                    'stock' => $template['stock'] + rand(-5, 10),
                    'sku' => 'SKU-' . $user->id . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'category' => $template['category'],
                    'image_url' => $template['image_url'],
                    'active' => rand(0, 10) > 1,
                ]);
            }

            echo "   \u2705 " . $userProducts->count() . " products for {$user->name}\n";
        }
    }
}
