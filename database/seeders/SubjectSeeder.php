<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matematika', 'code' => 'MTK'],
            ['name' => 'Fisika', 'code' => 'FIS'],
            ['name' => 'Kimia', 'code' => 'KIM'],
            ['name' => 'Biologi', 'code' => 'BIO'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIN'],
            ['name' => 'Bahasa Inggris', 'code' => 'BIG'],
            ['name' => 'IPA', 'code' => 'IPA'],
            ['name' => 'IPS', 'code' => 'IPS'],
            ['name' => 'Calistung', 'code' => 'CLS'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate([
                'name' => $subject['name'],
            ], [
                'code' => $subject['code'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Seeder mata pelajaran berhasil dijalankan.');
    }
}
