<?php

namespace Tests\Feature;

use App\Exports\StudentsExport;
use App\Exports\StudentsImportTemplate;
use App\Imports\StudentsImport;
use App\Models\Package;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StudentImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');

        $this->package = Package::create([
            'name' => 'Reguler Bulanan SMP',
            'type' => 'regular',
            'price' => 300000,
            'is_active' => true,
        ]);
    }

    public function test_import_template_headings(): void
    {
        $template = new StudentsImportTemplate();
        $headings = $template->headings();

        $this->assertContains('Nama Siswa', $headings);
        $this->assertContains('Kelas', $headings);
        $this->assertContains('Paket', $headings);
        $this->assertContains('No HP', $headings);
        $this->assertContains('Jatuh Tempo', $headings);
    }

    public function test_can_import_students_with_flexible_headers(): void
    {
        $this->actingAs($this->admin);

        $import = new StudentsImport();

        // Uji dengan header slug hasil export template (dengan fallback keys)
        $rows = new Collection([
            [
                'nama_siswa' => 'Rina Wati',
                'kelas_reguler_private' => 'Reguler',
                'paket' => 'Reguler Bulanan SMP',
                'orang_tua' => 'Dewi Wati',
                'no_hp' => '081234567891',
                'sekolah' => 'SMP 1',
                'kelas_sekolah' => 'Kelas 7',
                'alamat' => 'Jl. Mawar',
                'jatuh_tempo_1_28' => 15,
                'tanggal_gabung_yyyy_mm_dd' => '2026-03-01',
                'status_aktif_nonaktif_cuti' => 'Aktif',
            ],
            [
                'nama_siswa' => 'Doni Kusuma',
                'kelas' => 'reguler',
                'paket' => 'Reguler Bulanan SMP',
                'orang_tua' => 'Kusuma',
                'no_hp' => '081234567892',
                'sekolah' => 'SMP 2',
                'kelas_sekolah' => 'Kelas 8',
                'alamat' => 'Jl. Melati',
                'jatuh_tempo' => 10,
                'tanggal_gabung' => '2026-04-01',
                'status' => 'active',
            ],
        ]);

        $import->collection($rows);

        $this->assertEquals(2, $import->summary['success']);
        $this->assertEquals(0, $import->summary['failed']);

        $this->assertDatabaseHas('students', [
            'name' => 'Rina Wati',
            'parent_phone' => '081234567891',
            'due_day' => 15,
            'class_type' => 'regular',
        ]);

        $this->assertDatabaseHas('students', [
            'name' => 'Doni Kusuma',
            'parent_phone' => '081234567892',
            'due_day' => 10,
            'class_type' => 'regular',
        ]);
    }

    public function test_export_students_mapping(): void
    {
        $student = Student::create([
            'name' => 'Fajar Nugraha',
            'class_type' => 'regular',
            'package_id' => $this->package->id,
            'parent_name' => 'Nugraha',
            'parent_phone' => '081233334444',
            'due_day' => 7,
            'status' => 'active',
        ]);

        $export = new StudentsExport(Student::query());
        $mapped = $export->map($student);

        $this->assertEquals($student->id, $mapped[0]);
        $this->assertEquals('Fajar Nugraha', $mapped[1]);
        $this->assertEquals('Reguler', $mapped[2]);
        $this->assertEquals('Reguler Bulanan SMP', $mapped[3]);
        $this->assertEquals('Tgl 7', $mapped[9]);
        $this->assertEquals('Aktif', $mapped[11]);
    }
}

