<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User; // Para buscar managers
use Illuminate\Support\Arr;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtenemos algunos managers para asociarlos como creadores de clientes
        $managers = User::where('role', 'manager')->pluck('id')->toArray();

        // Lista de clientes de ejemplo
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
            ]
        ];

        foreach ($customers as $data) {
            Customer::firstOrCreate(
                ['email' => $data['email'], 'user_id' => Arr::random($managers)],
                $data
            );
        }

        $this->command->info('✅ Created ' . count($customers) . ' customers');
    }
}
