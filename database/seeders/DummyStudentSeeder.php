<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DummyStudentSeeder extends Seeder
{
    public function run(): void
    {
        $packages = Package::all();

        if ($packages->isEmpty()) {
            $this->command->error('⚠️ Jalankan PackageSeeder terlebih dahulu!');

            return;
        }

        $names = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gita', 'Hani', 'Irfan', 'Joko',
            'Kartika', 'Lina', 'Maya', 'Nanda', 'Oki', 'Putri', 'Qori', 'Rian', 'Sari', 'Tono'];

        $schools = ['SDN 1 Jakarta', 'SMPN 2 Bandung', 'SMAN 3 Surabaya', 'SD Islam Al-Azhar', 'SMP Kristen Penabur'];
        $subjects = ['Matematika', 'Fisika', 'Kimia', 'Bahasa Inggris', 'Biologi'];

        foreach ($names as $index => $name) {
            Student::create([
                'name' => $name.' '.fake()->lastName(),
                'class_type' => $index % 2 == 0 ? 'regular' : 'private',
                'package_id' => $packages->random()->id,
                'parent_name' => 'Bpk/Ibu '.fake()->lastName(),
                'parent_phone' => '08'.fake()->numerify('##########'), // 08 + 10 angka acak
                'school' => fake()->randomElement($schools),
                'school_grade' => fake()->randomElement(['SD Kelas 5', 'SMP Kelas 8', 'SMA Kelas 11']),
                'subject' => fake()->randomElement($subjects),
                'address' => fake()->address(),
                'due_day' => fake()->numberBetween(1, 28),
                'join_date' => fake()->dateTimeBetween('-3 months', 'now'),
                'status' => 'active',
                'created_by' => null,
            ]);
        }

        $this->command->info('✅ 20 Siswa Dummy berhasil dibuat!');
    }
}
