<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_monthly_invoices(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'type' => 'regular',
            'price' => 500000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $student = Student::create([
            'name' => 'Test Student',
            'class_type' => 'regular',
            'package_id' => $package->id,
            'parent_name' => 'Test Parent',
            'parent_phone' => '081234567890',
            'due_day' => 5,
            'status' => 'active',
        ]);

        $this->artisan('zigmath:generate-invoices')
            ->assertSuccessful();

        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'period' => now()->format('Y-m'),
            'amount' => 500000,
            'status' => 'unpaid',
        ]);
    }

    public function test_does_not_duplicate_invoices(): void
    {
        $package = Package::create([
            'name' => 'Test Package',
            'type' => 'regular',
            'price' => 500000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $student = Student::create([
            'name' => 'Test Student',
            'class_type' => 'regular',
            'package_id' => $package->id,
            'parent_name' => 'Test Parent',
            'parent_phone' => '081234567890',
            'due_day' => 5,
            'status' => 'active',
        ]);

        // Generate 2x
        $this->artisan('zigmath:generate-invoices');
        $this->artisan('zigmath:generate-invoices');

        $count = Invoice::where('student_id', $student->id)
            ->where('period', now()->format('Y-m'))
            ->count();

        $this->assertEquals(1, $count);
    }
}
