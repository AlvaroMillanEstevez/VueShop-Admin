<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "👥 Creando customers...\n";
        
        // Lista de clientes de ejemplo - son GLOBALES, no pertenecen a ningún usuario
        $customers = [
            [
                'name' => 'Ana García López',
                'email' => 'ana.garcia@email.com',
                'phone' => '+34 666 111 222',
                'address' => 'Calle Mayor 123',
                'city' => 'Madrid',
                'country' => 'España',
                'notes' => 'Cliente con historial frecuente de compras.'
            ],
            [
                'name' => 'Pedro Martínez Ruiz',
                'email' => 'pedro.martinez@email.com',
                'phone' => '+34 666 333 444',
                'address' => 'Av. Constitución 45',
                'city' => 'Barcelona',
                'country' => 'España',
                'notes' => null
            ],
            [
                'name' => 'Carmen Fernández Silva',
                'email' => 'carmen.fernandez@email.com',
                'phone' => '+34 666 555 666',
                'address' => 'Plaza España 78',
                'city' => 'Valencia',
                'country' => 'España',
                'notes' => 'Solicita factura electrónica siempre.'
            ],
            [
                'name' => 'Miguel Ángel Torres',
                'email' => 'miguel.torres@email.com',
                'phone' => '+34 666 777 888',
                'address' => 'Calle Alcalá 234',
                'city' => 'Madrid',
                'country' => 'España',
                'notes' => null
            ],
            [
                'name' => 'Isabel Moreno Castro',
                'email' => 'isabel.moreno@email.com',
                'phone' => '+34 666 999 000',
                'address' => 'Gran Vía 567',
                'city' => 'Sevilla',
                'country' => 'España',
                'notes' => 'Interesada en productos ecológicos.'
            ],
            [
                'name' => 'Roberto Jiménez Vega',
                'email' => 'roberto.jimenez@email.com',
                'phone' => '+34 677 111 222',
                'address' => 'Paseo de Gracia 89',
                'city' => 'Barcelona',
                'country' => 'España',
                'notes' => null
            ],
            [
                'name' => 'Lucía Sánchez Ortega',
                'email' => 'lucia.sanchez@email.com',
                'phone' => '+34 677 333 444',
                'address' => 'Calle de la Paz 12',
                'city' => 'Bilbao',
                'country' => 'España',
                'notes' => 'Prefiere contacto vía email.'
            ],
            [
                'name' => 'Francisco Herrera Díaz',
                'email' => 'francisco.herrera@email.com',
                'phone' => '+34 677 555 666',
                'address' => 'Rambla de Catalunya 345',
                'city' => 'Barcelona',
                'country' => 'España',
                'notes' => null
            ],
            [
                'name' => 'Laura Ruiz Morales',
                'email' => 'laura.ruiz@email.com',
                'phone' => '+34 677 777 888',
                'address' => 'Calle Sol 99',
                'city' => 'Valencia',
                'country' => 'España',
                'notes' => 'Cliente VIP desde 2020.'
            ],
            [
                'name' => 'Carlos Vázquez Romero',
                'email' => 'carlos.vazquez@email.com',
                'phone' => '+34 677 999 000',
                'address' => 'Av. Libertad 156',
                'city' => 'Zaragoza',
                'country' => 'España',
                'notes' => null
            ],
            [
                'name' => 'Marta González Pérez',
                'email' => 'marta.gonzalez@email.com',
                'phone' => '+34 688 111 222',
                'address' => 'Plaza del Carmen 23',
                'city' => 'Granada',
                'country' => 'España',
                'notes' => 'Prefiere entrega en horario de mañana.'
            ],
            [
                'name' => 'Alejandro Muñoz López',
                'email' => 'alejandro.munoz@email.com',
                'phone' => '+34 688 333 444',
                'address' => 'Calle Nueva 67',
                'city' => 'Málaga',
                'country' => 'España',
                'notes' => null
            ]
        ];

        foreach ($customers as $data) {
            // Los customers son globales - solo verificamos por email
            Customer::firstOrCreate(
                ['email' => $data['email']], // Solo verificar email, sin user_id
                $data
            );
        }

        echo "   ✅ Creados " . count($customers) . " customers\n";
        echo "   ℹ️ Los customers son globales - cualquier vendedor puede procesarles pedidos\n";
    }
}