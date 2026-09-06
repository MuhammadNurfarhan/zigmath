<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            // ===== REGULER =====
            [
                'name' => 'Reguler SD - Bulanan',
                'type' => 'regular',
                'price' => 300000,
                'duration_months' => 1,
                'sessions_count' => 8,
                'duration_minutes' => 90,
                'description' => 'Paket reguler siswa SD, 8x pertemuan per bulan',
                'is_active' => true,
            ],
            [
                'name' => 'Reguler SMP - Bulanan',
                'type' => 'regular',
                'price' => 350000,
                'duration_months' => 1,
                'sessions_count' => 8,
                'duration_minutes' => 90,
                'description' => 'Paket reguler siswa SMP, 8x pertemuan per bulan',
                'is_active' => true,
            ],
            [
                'name' => 'Reguler SMA - Bulanan',
                'type' => 'regular',
                'price' => 400000,
                'duration_months' => 1,
                'sessions_count' => 8,
                'duration_minutes' => 120,
                'description' => 'Paket reguler siswa SMA, 8x pertemuan per bulan',
                'is_active' => true,
            ],
            [
                'name' => 'Reguler SD - Semester',
                'type' => 'regular',
                'price' => 1600000,
                'duration_months' => 6,
                'sessions_count' => 48,
                'duration_minutes' => 90,
                'description' => 'Paket hemat reguler SD untuk 6 bulan',
                'is_active' => true,
            ],

            // ===== PRIVATE =====
            [
                'name' => 'Private SD - Bulanan (8 Sesi)',
                'type' => 'private',
                'price' => 800000,
                'duration_months' => 1,
                'sessions_count' => 8,
                'duration_minutes' => 90,
                'description' => 'Private 1-on-1 siswa SD, 8x pertemuan',
                'is_active' => true,
            ],
            [
                'name' => 'Private SMP - Bulanan (8 Sesi)',
                'type' => 'private',
                'price' => 900000,
                'duration_months' => 1,
                'sessions_count' => 8,
                'duration_minutes' => 90,
                'description' => 'Private 1-on-1 siswa SMP, 8x pertemuan',
                'is_active' => true,
            ],
            [
                'name' => 'Private SMA - Bulanan (8 Sesi)',
                'type' => 'private',
                'price' => 1000000,
                'duration_months' => 1,
                'sessions_count' => 8,
                'duration_minutes' => 120,
                'description' => 'Private 1-on-1 siswa SMA, 8x pertemuan',
                'is_active' => true,
            ],
            [
                'name' => 'Private - Paket 4 Sesi',
                'type' => 'private',
                'price' => 450000,
                'duration_months' => 1,
                'sessions_count' => 4,
                'duration_minutes' => 90,
                'description' => 'Paket private fleksibel 4 sesi',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }

        $this->command->info('✅ '.count($packages).' paket berhasil dibuat.');
    }
}
