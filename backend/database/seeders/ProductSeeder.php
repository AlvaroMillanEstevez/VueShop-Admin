<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Latest Apple smartphone with titanium design',
                'price' => 1199.99,
                'stock' => 25,
                'sku' => 'IPH15P-128',
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1592286130927-570121fadf8b?w=400',
                'active' => true,
            ],
            [
                'name' => 'MacBook Air M3',
                'description' => 'Lightweight laptop with M3 chip',
                'price' => 1299.99,
                'stock' => 15,
                'sku' => 'MBA-M3-256',
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=400',
                'active' => true,
            ],
            [
                'name' => 'Nike Air Max 270',
                'description' => 'Comfortable running shoes',
                'price' => 159.99,
                'stock' => 45,
                'sku' => 'NIKE-AM270-42',
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400',
                'active' => true,
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'description' => 'Noise-canceling wireless headphones',
                'price' => 399.99,
                'stock' => 30,
                'sku' => 'SONY-WH1000-BLK',
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400',
                'active' => true,
            ],
            [
                'name' => 'Samsung 55" QLED TV',
                'description' => '4K Smart TV with Quantum Dot technology',
                'price' => 899.99,
                'stock' => 12,
                'sku' => 'SAM-Q55-4K',
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=400',
                'active' => true,
            ],
            [
                'name' => 'Adidas Ultraboost 22',
                'description' => 'Premium running shoes with boost technology',
                'price' => 189.99,
                'stock' => 35,
                'sku' => 'ADI-UB22-41',
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400',
                'active' => true,
            ],
            [
                'name' => 'Dell XPS 13',
                'description' => 'Ultra-portable laptop with InfinityEdge display',
                'price' => 1099.99,
                'stock' => 20,
                'sku' => 'DELL-XPS13-512',
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400',
                'active' => true,
            ],
            [
                'name' => 'Levi\'s 501 Jeans',
                'description' => 'Classic straight-leg denim jeans',
                'price' => 89.99,
                'stock' => 60,
                'sku' => 'LEVI-501-32W',
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400',
                'active' => true,
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}