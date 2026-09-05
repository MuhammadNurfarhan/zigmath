<?php

namespace Tests\Feature;

use App\Filament\Resources\StudentResource\Pages\CreateStudent;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        $this->package = Package::create([
            'name' => 'Reguler SD - Bulanan',
            'type' => 'regular',
            'price' => 300000,
            'duration_months' => 1,
            'sessions_count' => 8,
            'duration_minutes' => 90,
            'is_active' => true,
        ]);
    }

    public function test_can_create_student(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateStudent::class)
            ->fillForm([
                'name' => 'Budi Santoso',
                'class_type' => 'regular',
                'package_id' => $this->package->id,
                'parent_name' => 'Ahmad Santoso',
                'parent_phone' => '081234567890',
                'school' => 'SD Negeri 1',
                'school_grade' => 'SD Kelas 5',
                'subject' => 'Matematika',
                'address' => 'Jl. Test No. 1',
                'due_day' => 5,
                'join_date' => now()->toDateString(),
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('students', [
            'name' => 'Budi Santoso',
            'parent_phone' => '081234567890',
            'parent_name' => 'Ahmad Santoso',
            'due_day' => 5,
            'status' => 'active',
        ]);
    }

    public function test_student_requires_valid_phone(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateStudent::class)
            ->fillForm([
                'name' => 'Budi Santoso',
                'class_type' => 'regular',
                'package_id' => $this->package->id,
                'parent_name' => 'Ahmad Santoso',
                'parent_phone' => '12345',
                'address' => 'Jl. Test No. 1',
                'due_day' => 5,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'parent_phone',
            ]);

        $this->assertDatabaseMissing('students', [
            'name' => 'Budi Santoso',
            'parent_phone' => '12345',
        ]);
    }

    public function test_student_requires_due_day_between_1_and_28(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateStudent::class)
            ->fillForm([
                'name' => 'Budi Santoso',
                'class_type' => 'regular',
                'package_id' => $this->package->id,
                'parent_name' => 'Ahmad Santoso',
                'parent_phone' => '081234567890',
                'address' => 'Jl. Test No. 1',
                'due_day' => 31,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'due_day',
            ]);

        $this->assertDatabaseMissing('students', [
            'name' => 'Budi Santoso',
            'due_day' => 31,
        ]);
    }
}
