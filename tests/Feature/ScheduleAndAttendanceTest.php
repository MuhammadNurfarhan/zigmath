<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Subject $subject;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');

        $package = Package::create([
            'name' => 'Paket Reguler SMP',
            'type' => 'regular',
            'price' => 350000,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'name' => 'Andi Pratama',
            'class_type' => 'regular',
            'package_id' => $package->id,
            'parent_name' => 'Bambang Pratama',
            'parent_phone' => '081299998888',
            'due_day' => 10,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'name' => 'Matematika SMP',
            'code' => 'MTK-SMP',
            'description' => 'Matematika tingkat SMP',
            'is_active' => true,
        ]);
    }

    public function test_can_create_schedule_and_assign_student(): void
    {
        $schedule = Schedule::create([
            'tutor_name' => 'Kak Sarah',
            'subject_id' => $this->subject->id,
            'day_of_week' => 1, // Senin
            'start_time' => '15:00:00',
            'end_time' => '16:30:00',
            'room' => 'Ruang A',
            'status' => 'active',
        ]);

        $schedule->students()->attach($this->student->id, ['status' => 'active']);

        $this->assertDatabaseHas('schedules', [
            'tutor_name' => 'Kak Sarah',
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
        ]);

        $this->assertDatabaseHas('schedule_students', [
            'schedule_id' => $schedule->id,
            'student_id' => $this->student->id,
        ]);

        $this->assertTrue($schedule->students->contains($this->student));
    }

    public function test_prevents_tutor_schedule_conflict(): void
    {
        Schedule::create([
            'tutor_name' => 'Kak Budi',
            'subject_id' => $this->subject->id,
            'day_of_week' => 2, // Selasa
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        // Jadwal bertabrakan jam untuk tutor yang sama
        Schedule::create([
            'tutor_name' => 'Kak Budi',
            'subject_id' => $this->subject->id,
            'day_of_week' => 2, // Hari sama
            'start_time' => '14:30:00', // Bentrok
            'end_time' => '16:00:00',
            'status' => 'active',
        ]);
    }

    public function test_can_record_attendance(): void
    {
        $schedule = Schedule::create([
            'tutor_name' => 'Kak Sarah',
            'subject_id' => $this->subject->id,
            'day_of_week' => 3,
            'start_time' => '15:00:00',
            'end_time' => '16:30:00',
            'status' => 'active',
        ]);

        $attendance = Attendance::create([
            'student_id' => $this->student->id,
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'status' => 'hadir',
            'notes' => 'Hadir tepat waktu',
            'recorded_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'schedule_id' => $schedule->id,
            'status' => 'hadir',
            'recorded_by' => $this->admin->id,
        ]);

        $this->assertEquals($this->student->id, $attendance->student->id);
        $this->assertEquals($schedule->id, $attendance->schedule->id);
    }
}
