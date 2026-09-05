<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_make_partial_payment(): void
    {
        $admin = User::factory()->create();

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

        $invoice = Invoice::create([
            'invoice_no' => 'INV/ZGM/TEST/0001',
            'student_id' => $student->id,
            'period' => now()->format('Y-m'),
            'due_date' => now()->addDays(10),
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_balance' => 500000,
            'status' => 'unpaid',
        ]);

        // Simulasi bayar 200rb
        $payment = $invoice->payments()->create([
            'payment_no' => 'PAY/TEST/0001',
            'amount' => 200000,
            'method' => 'cash',
            'paid_at' => now(),
            'verified_by' => $admin->id,
        ]);

        $invoice->update([
            'paid_amount' => $invoice->paid_amount + 200000,
            'remaining_balance' => $invoice->remaining_balance - 200000,
            'status' => 'partial',
        ]);

        $invoice->refresh();

        $this->assertEquals(200000, $invoice->paid_amount);
        $this->assertEquals(300000, $invoice->remaining_balance);
        $this->assertEquals('partial', $invoice->status);
    }

    public function test_invoice_becomes_paid_when_fully_paid(): void
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

        $invoice = Invoice::create([
            'invoice_no' => 'INV/ZGM/TEST/0002',
            'student_id' => $student->id,
            'period' => now()->format('Y-m'),
            'due_date' => now()->addDays(10),
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_balance' => 500000,
            'status' => 'unpaid',
        ]);

        // Bayar penuh
        $invoice->payments()->create([
            'payment_no' => 'PAY/TEST/0002',
            'amount' => 500000,
            'method' => 'transfer',
            'paid_at' => now(),
        ]);

        $invoice->update([
            'paid_amount' => 500000,
            'remaining_balance' => 0,
            'status' => 'paid',
        ]);

        $invoice->refresh();

        $this->assertTrue($invoice->isPaid());
        $this->assertEquals(0, $invoice->remaining_balance);
        $this->assertEquals('paid', $invoice->status);
    }
}
