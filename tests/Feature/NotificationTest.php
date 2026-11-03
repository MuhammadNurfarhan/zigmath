<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');

        $package = Package::create([
            'name' => 'Paket Belajar',
            'type' => 'regular',
            'price' => 300000,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'name' => 'Dewi Lestari',
            'class_type' => 'regular',
            'package_id' => $package->id,
            'parent_name' => 'Wahyudi',
            'parent_phone' => '081288887777',
            'due_day' => 5,
            'status' => 'active',
        ]);
    }

    public function test_generates_notifications_for_overdue_and_upcoming_invoices(): void
    {
        // Tagihan hampir jatuh tempo (besok)
        Invoice::create([
            'invoice_no' => 'INV/ZGM/202611/DUE1',
            'student_id' => $this->student->id,
            'period' => '2026-11',
            'due_date' => now()->addDay(),
            'amount' => 300000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 300000,
            'status' => 'unpaid',
        ]);

        // Tagihan overdue
        Invoice::create([
            'invoice_no' => 'INV/ZGM/202610/OVD1',
            'student_id' => $this->student->id,
            'period' => '2026-10',
            'due_date' => now()->subDays(5),
            'amount' => 300000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 300000,
            'status' => 'overdue',
        ]);

        // Jadwal hari ini
        $subject = Subject::create([
            'name' => 'Fisika',
            'code' => 'FIS',
            'is_active' => true,
        ]);

        Schedule::create([
            'tutor_name' => 'Kak Ryan',
            'subject_id' => $subject->id,
            'day_of_week' => now()->dayOfWeekIso,
            'start_time' => '16:00:00',
            'end_time' => '17:30:00',
            'status' => 'active',
        ]);

        $this->artisan('zigmath:generate-notifications')->assertSuccessful();

        // Verifikasi notifikasi terbuat
        $this->assertDatabaseHas('app_notifications', [
            'type' => 'payment_due',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'type' => 'overdue',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'type' => 'schedule_today',
        ]);

        $countBefore = AppNotification::count();

        // Jalankan lagi: tidak boleh ada duplikasi karena deduplikasi key
        $this->artisan('zigmath:generate-notifications')->assertSuccessful();

        $countAfter = AppNotification::count();
        $this->assertEquals($countBefore, $countAfter);
    }
}

