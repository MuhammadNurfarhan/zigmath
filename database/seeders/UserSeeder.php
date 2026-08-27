<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat roles dulu
        User::createDefaultRoles();

        // Buat super admin
        $superAdmin = User::create([
            'name' => 'Super Admin Zigmath',
            'email' => 'admin@zigmath.test',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
            'position' => 'Super Administrator',
            'is_active' => true,
            'email_verified_at' => now(), // ← WAJIB untuk bisa login
        ]);
        $superAdmin->assignRole('super-admin');

        // Buat finance
        $finance = User::create([
            'name' => 'Finance Zigmath',
            'email' => 'finance@zigmath.test',
            'password' => bcrypt('password'),
            'position' => 'Finance Staff',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $finance->assignRole('finance');

        // Buat operator
        $operator = User::create([
            'name' => 'Operator Zigmath',
            'email' => 'operator@zigmath.test',
            'password' => bcrypt('password'),
            'position' => 'Operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $operator->assignRole('operator');
    }
}
