<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,   // 1. Buat Roles & Permissions
            UserSeeder::class,             // 2. Buat User (Super Admin, Admin, Finance, Operator)
            SubjectSeeder::class,          // 3. Buat Mata Pelajaran
            PackageSeeder::class,          // 4. Buat Paket Bimbel
            DummyStudentSeeder::class,     // 5. Buat 20 Siswa Dummy
        ]);
    }
}
