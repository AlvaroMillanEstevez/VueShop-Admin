<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        echo "👥 Creando clientes...\n";
        
        $faker = Faker::create('es_ES');
        $managers = User::where('role', 'manager')->get();

        $customerTemplates = [
            ['name' => 'María García López', 'email' => 'maria.garcia@email.com', 'city' => 'Madrid'],
            ['name' => 'Carlos Rodríguez Martín', 'email' => 'carlos.rodriguez@email.com', 'city' => 'Barcelona'],
            ['name' => 'Ana Fernández Silva', 'email' => 'ana.fernandez@email.com', 'city' => 'Valencia'],
            ['name' => 'David González Ruiz', 'email' => 'david.gonzalez@email.com', 'city' => 'Sevilla'],
            ['name' => 'Laura Sánchez Torres', 'email' => 'laura.sanchez@email.com', 'city' => 'Málaga'],
            ['name' => 'Roberto Jiménez Mora', 'email' => 'roberto.jimenez@email.com', 'city' => 'Bilbao'],
            ['name' => 'Elena Martín Campos', 'email' => 'elena.martin@email.com', 'city' => 'Zaragoza'],
            ['name' => 'Miguel Herrera López', 'email' => 'miguel.herrera@email.com', 'city' => 'Murcia'],
            ['name' => 'Isabel Ruiz Moreno', 'email' => 'isabel.ruiz@email.com', 'city' => 'Palma'],
            ['name' => 'Francisco Díaz Vega', 'email' => 'francisco.diaz@email.com', 'city' => 'Las Palmas'],
            ['name' => 'Carmen Morales Gil', 'email' => 'carmen.morales@email.com', 'city' => 'Alicante'],
            ['name' => 'Antonio Jiménez Ramos', 'email' => 'antonio.jimenez@email.com', 'city' => 'Córdoba'],
            ['name' => 'Pilar Álvarez Ortega', 'email' => 'pilar.alvarez@email.com', 'city' => 'Valladolid'],
            ['name' => 'José Luis Castro Peña', 'email' => 'joseluis.castro@email.com', 'city' => 'Vigo'],
            ['name' => 'Rosario Delgado Vargas', 'email' => 'rosario.delgado@email.com', 'city' => 'Gijón'],
        ];

        foreach ($managers as $user) {
            // Cada manager tendrá entre 8-12 clientes
            $customerCount = rand(8, 12);
            $usedTemplates = collect($customerTemplates)->shuffle()->take($customerCount);
            
            foreach ($usedTemplates as $index => $template) {
                Customer::create([
                    'user_id' => $user->id,
                    'name' => $template['name'],
                    'email' => $template['email'],
                    'phone' => $faker->optional(0.8)->phoneNumber,
                    'address' => $faker->optional(0.7)->address,
                    'city' => $template['city'],
                    'country' => 'Spain',
                    'notes' => $faker->optional(0.3)->sentence,
                ]);
            }
            
            echo "   ✅ {$customerCount} clientes para {$user->name}\n";
        }
    }
}