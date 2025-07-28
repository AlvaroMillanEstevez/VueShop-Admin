<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'María García López',
                'email' => 'maria.garcia@email.com',
                'phone' => '+34 612 345 678',
                'address' => 'Calle Gran Vía 123, 4º A',
                'city' => 'Madrid',
                'country' => 'Spain',
                'last_order_at' => Carbon::now()->subDays(2),
                'total_spent' => 2450.75,
            ],
            [
                'name' => 'Carlos Rodríguez Martín',
                'email' => 'carlos.rodriguez@email.com',
                'phone' => '+34 678 901 234',
                'address' => 'Avenida Diagonal 456, 2º B',
                'city' => 'Barcelona',
                'country' => 'Spain',
                'last_order_at' => Carbon::now()->subDays(5),
                'total_spent' => 1890.50,
            ],
            [
                'name' => 'Ana Fernández Silva',
                'email' => 'ana.fernandez@email.com',
                'phone' => '+34 654 321 098',
                'address' => 'Calle Larios 789',
                'city' => 'Málaga',
                'country' => 'Spain',
                'last_order_at' => Carbon::now()->subDays(1),
                'total_spent' => 3200.25,
            ],
            [
                'name' => 'David González Ruiz',
                'email' => 'david.gonzalez@email.com',
                'phone' => '+34 698 765 432',
                'address' => 'Plaza del Pilar 12',
                'city' => 'Valencia',
                'country' => 'Spain',
                'last_order_at' => Carbon::now()->subDays(7),
                'total_spent' => 1560.80,
            ],
            [
                'name' => 'Laura Sánchez Torres',
                'email' => 'laura.sanchez@email.com',
                'phone' => '+34 687 543 210',
                'address' => 'Calle Mayor 345',
                'city' => 'Sevilla',
                'country' => 'Spain',
                'last_order_at' => Carbon::now()->subDays(3),
                'total_spent' => 2780.90,
            ],
            [
                'name' => 'Roberto Jiménez Mora',
                'email' => 'roberto.jimenez@email.com',
                'phone' => '+34 665 432 109',
                'address' => 'Avenida de la Paz 678',
                'city' => 'Bilbao',
                'country' => 'Spain',
                'last_order_at' => Carbon::now()->subDays(4),
                'total_spent' => 1920.60,
            ]
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}