<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PackageSeeder::class,          // 1. Buat Paket Bimbel
            RolePermissionSeeder::class,   // 2. Buat Roles (Super Admin, Finance, dll)
            DummyStudentSeeder::class,     // 3. Buat 20 Siswa Dummy
        ]);
    }
}
