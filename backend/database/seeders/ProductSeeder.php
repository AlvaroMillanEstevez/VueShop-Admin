<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        echo "📦 Creando productos...\n";
        
        $managers = User::where('role', 'manager')->get();
        
        $productTemplates = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'El último iPhone con chip A17 Pro y cámara avanzada',
                'price' => 1199.99,
                'stock' => 25,
                'category' => 'Smartphones',
                'image_url' => 'https://images.unsplash.com/photo-1592286130927-570121fadf8b?w=400'
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'description' => 'Smartphone Android premium con AI integrada',
                'price' => 899.99,
                'stock' => 30,
                'category' => 'Smartphones',
                'image_url' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400'
            ],
            [
                'name' => 'MacBook Air M3',
                'description' => 'Laptop ultraligera con chip M3 y 8GB RAM',
                'price' => 1299.99,
                'stock' => 15,
                'category' => 'Laptops',
                'image_url' => 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=400'
            ],
            [
                'name' => 'Dell XPS 13',
                'description' => 'Laptop premium con procesador Intel i7',
                'price' => 1199.99,
                'stock' => 12,
                'category' => 'Laptops',
                'image_url' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400'
            ],
            [
                'name' => 'iPad Pro 12.9"',
                'description' => 'Tablet profesional con chip M2 y pantalla Liquid Retina',
                'price' => 1099.99,
                'stock' => 20,
                'category' => 'Tablets',
                'image_url' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400'
            ],
            [
                'name' => 'AirPods Pro 2',
                'description' => 'Auriculares inalámbricos con cancelación de ruido',
                'price' => 249.99,
                'stock' => 50,
                'category' => 'Audio',
                'image_url' => 'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=400'
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'description' => 'Auriculares over-ear con la mejor cancelación de ruido',
                'price' => 399.99,
                'stock' => 35,
                'category' => 'Audio',
                'image_url' => 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400'
            ],
            [
                'name' => 'Apple Watch Series 9',
                'description' => 'Smartwatch con GPS y monitoreo de salud avanzado',
                'price' => 429.99,
                'stock' => 40,
                'category' => 'Wearables',
                'image_url' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=400'
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'description' => 'Consola híbrida con pantalla OLED de 7 pulgadas',
                'price' => 349.99,
                'stock' => 25,
                'category' => 'Gaming',
                'image_url' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400'
            ],
            [
                'name' => 'PlayStation 5',
                'description' => 'Consola de nueva generación con SSD ultrarrápido',
                'price' => 499.99,
                'stock' => 8,
                'category' => 'Gaming',
                'image_url' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=400'
            ],
            [
                'name' => 'Nike Air Max 270',
                'description' => 'Zapatillas deportivas con amortiguación Air Max',
                'price' => 159.99,
                'stock' => 45,
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400'
            ],
            [
                'name' => 'Adidas Ultraboost 22',
                'description' => 'Zapatillas premium para running con boost technology',
                'price' => 189.99,
                'stock' => 35,
                'category' => 'Fashion',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'
            ]
        ];

        foreach ($managers as $user) {
            // Cada manager tendrá una selección aleatoria de 6-9 productos
            $userProducts = collect($productTemplates)->shuffle()->take(rand(6, 9));
            
            foreach ($userProducts as $index => $template) {
                Product::create([
                    'user_id' => $user->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'price' => round($template['price'] + rand(-50, 50), 2),
                    'stock' => $template['stock'] + rand(-5, 10), // Variación en stock
                    'sku' => 'SKU-' . $user->id . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'category' => $template['category'],
                    'image_url' => $template['image_url'],
                    'active' => rand(0, 10) > 1, // 90% activos
                ]);
            }
            
            echo "   ✅ " . $userProducts->count() . " productos para {$user->name}\n";
        }
    }
}