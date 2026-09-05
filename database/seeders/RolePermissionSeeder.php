<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Permissions
        $permissions = [
            // Siswa
            'view students', 'create students', 'edit students', 'delete students', 'export students', 'import students',
            // Paket
            'view packages', 'create packages', 'edit packages', 'delete packages',
            // Invoice
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices', 'generate invoices',
            // Payment
            'view payments', 'create payments', 'edit payments', 'delete payments', 'verify payments',
            // Laporan
            'view reports', 'export reports',
            // Settings
            'view settings', 'edit settings',
            // User Management
            'view users', 'create users', 'edit users', 'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Role: Super Admin (semua akses)
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Role: Finance (fokus keuangan)
        $finance = Role::firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $finance->givePermissionTo([
            'view students', 'view packages',
            'view invoices', 'create invoices', 'edit invoices', 'generate invoices',
            'view payments', 'create payments', 'verify payments',
            'view reports', 'export reports',
        ]);

        // Role: Operator (input data dasar)
        $operator = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
        $operator->givePermissionTo([
            'view students', 'create students', 'edit students',
            'view packages',
            'view invoices',
            'view payments', 'create payments',
        ]);

        // Assign role ke user pertama sebagai Super Admin
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('Super Admin');
        }

        $this->command->info('✅ Roles dan Permissions berhasil dibuat.');
    }
}
