<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        echo "👤 Creando usuarios...\n";

        // Admin user - tendrá acceso completo y panel de administración
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@vueshop.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Manager users - usuarios normales que usan la app
        $manager1 = User::create([
            'name' => 'Juan García Pérez',
            'email' => 'juan@vueshop.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $manager2 = User::create([
            'name' => 'María López Silva',
            'email' => 'maria@vueshop.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $manager3 = User::create([
            'name' => 'Carlos Rodríguez Martín',
            'email' => 'carlos@vueshop.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        echo "   ✅ Admin: {$admin->name} ({$admin->email})\n";
        echo "   ✅ Manager: {$manager1->name} ({$manager1->email})\n";
        echo "   ✅ Manager: {$manager2->name} ({$manager2->email})\n";
        echo "   ✅ Manager: {$manager3->name} ({$manager3->email})\n";
    }
}