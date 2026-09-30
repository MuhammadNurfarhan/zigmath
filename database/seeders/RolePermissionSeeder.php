<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'view dashboard',

            // Siswa
            'view students',
            'create students',
            'edit students',
            'delete students',
            'export students',
            'import students',

            // Paket
            'view packages',
            'create packages',
            'edit packages',
            'delete packages',

            // Mata Pelajaran
            'view subjects',
            'create subjects',
            'edit subjects',
            'delete subjects',

            // Invoice
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'generate invoices',
            'export invoices',
            'print invoices',
            'pay invoices',

            // Payment
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            'verify payments',
            'export payments',
            'print payments',

            // Jadwal
            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',

            // Absensi
            'view attendances',
            'create attendances',
            'edit attendances',
            'delete attendances',
            'export attendances',

            // Laporan
            'view reports',
            'export reports',
            'print reports',

            // Notifikasi
            'view notifications',
            'manage notifications',

            // Settings
            'view settings',
            'edit settings',

            // Backup
            'view backup',
            'run backup',

            // Profil
            'view profile',
            'update profile',

            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ==================== SUPER ADMIN ====================
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::all());

        // ==================== ADMIN ====================
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions([
            'view dashboard',

            'view students',
            'create students',
            'edit students',
            'delete students',
            'export students',
            'import students',

            'view packages',
            'create packages',
            'edit packages',
            'delete packages',

            'view subjects',
            'create subjects',
            'edit subjects',
            'delete subjects',

            'view invoices',
            'create invoices',
            'edit invoices',
            'generate invoices',
            'export invoices',
            'print invoices',
            'pay invoices',

            'view payments',
            'create payments',
            'verify payments',
            'export payments',
            'print payments',

            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',

            'view attendances',
            'create attendances',
            'edit attendances',
            'delete attendances',
            'export attendances',

            'view reports',
            'export reports',
            'print reports',

            'view notifications',

            'view settings',

            'view profile',
            'update profile',
        ]);

        // ==================== FINANCE ====================
        $finance = Role::firstOrCreate([
            'name' => 'finance',
            'guard_name' => 'web',
        ]);

        $finance->syncPermissions([
            'view dashboard',

            'view students',
            'export students',

            'view packages',

            'view invoices',
            'create invoices',
            'edit invoices',
            'generate invoices',
            'export invoices',
            'print invoices',
            'pay invoices',

            'view payments',
            'create payments',
            'verify payments',
            'export payments',
            'print payments',

            'view schedules',

            'view attendances',

            'view reports',
            'export reports',
            'print reports',

            'view notifications',

            'view profile',
            'update profile',
        ]);

        // ==================== OPERATOR ====================
        $operator = Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'web',
        ]);

        $operator->syncPermissions([
            'view dashboard',

            'view students',
            'create students',
            'edit students',
            'export students',
            'import students',

            'view packages',

            'view subjects',
            'create subjects',
            'edit subjects',

            'view invoices',

            'view payments',
            'create payments',

            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',

            'view attendances',
            'create attendances',
            'edit attendances',
            'export attendances',

            'view notifications',

            'view profile',
            'update profile',
        ]);

        // Assign user pertama sebagai super-admin
        $firstUser = User::first();

        if ($firstUser) {
            $firstUser->syncRoles(['super-admin']);
        }

        $this->command->info('✅ Roles dan permissions Zigmath berhasil dibuat.');
    }
}
