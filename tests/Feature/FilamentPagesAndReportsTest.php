<?php

namespace Tests\Feature;

use App\Filament\Pages\DailyAttendancePage;
use App\Filament\Pages\RekapAbsensiPage;
use App\Filament\Pages\ReportArrearsPage;
use App\Filament\Pages\ReportIncomePage;
use App\Filament\Pages\ReportStudentsPage;
use App\Filament\Pages\SettingsPage;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPagesAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Student $student;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');

        $this->package = Package::create([
            'name' => 'Reguler SMP',
            'type' => 'regular',
            'price' => 350000,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'name' => 'Siti Aminah',
            'class_type' => 'regular',
            'package_id' => $this->package->id,
            'parent_name' => 'Aminah',
            'parent_phone' => '081234567811',
            'due_day' => 10,
            'status' => 'active',
        ]);
    }

    public function test_report_arrears_page_loads_and_shows_unpaid_invoices(): void
    {
        Invoice::create([
            'invoice_no' => 'INV/ZGM/202611/ARR1',
            'student_id' => $this->student->id,
            'period' => '2026-11',
            'due_date' => now()->addDays(5),
            'amount' => 350000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 350000,
            'status' => 'unpaid',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ReportArrearsPage::class)
            ->assertSuccessful();
    }

    public function test_report_income_page_loads_successfully(): void
    {
        $invoice = Invoice::create([
            'invoice_no' => 'INV/ZGM/202611/INC1',
            'student_id' => $this->student->id,
            'period' => '2026-11',
            'due_date' => now()->addDays(5),
            'amount' => 350000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 350000,
            'status' => 'unpaid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 350000,
            'method' => 'cash',
            'verified_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ReportIncomePage::class)
            ->assertSuccessful();
    }

    public function test_report_students_page_loads_successfully(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ReportStudentsPage::class)
            ->assertSuccessful();
    }

    public function test_daily_attendance_and_rekap_page_load(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DailyAttendancePage::class)
            ->assertSuccessful();

        Livewire::actingAs($this->admin)
            ->test(RekapAbsensiPage::class)
            ->assertSuccessful();
    }

    public function test_settings_page_can_save_settings(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->assertSuccessful()
            ->fillForm([
                'bimbel_name' => 'Zigmath Education Center',
                'bimbel_phone' => '081299990000',
                'invoice_prefix' => 'INV/TEST/',
                'late_fee_type' => 'none',
                'invoice_show_logo' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Zigmath Education Center', \App\Models\Setting::get('bimbel_name'));
        $this->assertEquals('INV/TEST/', \App\Models\Setting::get('invoice_prefix'));
        $this->assertTrue(\App\Models\Setting::get('invoice_show_logo'));
    }
}

