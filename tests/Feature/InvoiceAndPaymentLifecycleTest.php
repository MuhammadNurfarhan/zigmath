<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceAndPaymentLifecycleTest extends TestCase
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
            'name' => 'Reguler SMA',
            'type' => 'regular',
            'price' => 400000,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'name' => 'Citra Kirana',
            'class_type' => 'regular',
            'package_id' => $package->id,
            'parent_name' => 'Hendro',
            'parent_phone' => '081234567899',
            'due_day' => 10,
            'status' => 'active',
        ]);
    }

    public function test_centralized_invoice_number_generation(): void
    {
        $period = '2026-11';
        $inv1 = Invoice::generateInvoiceNumber($period);
        $this->assertStringContainsString('202611/0001', $inv1);

        Invoice::create([
            'invoice_no' => $inv1,
            'student_id' => $this->student->id,
            'period' => $period,
            'due_date' => now()->addDays(5),
            'amount' => 400000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 400000,
            'status' => 'unpaid',
        ]);

        $inv2 = Invoice::generateInvoiceNumber($period);
        $this->assertStringContainsString('202611/0002', $inv2);
    }

    public function test_payment_creation_automatically_updates_invoice_balance(): void
    {
        $invoice = Invoice::create([
            'invoice_no' => 'INV/ZGM/202611/9001',
            'student_id' => $this->student->id,
            'period' => '2026-11',
            'due_date' => now()->addDays(5),
            'amount' => 400000,
            'discount' => 50000,
            'paid_amount' => 0,
            'remaining_balance' => 350000,
            'status' => 'unpaid',
        ]);

        // Bayar cicilan 150rb
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 150000,
            'method' => 'cash',
            'verified_by' => $this->admin->id,
            'paid_at' => now(),
        ]);

        // Cek payment_no auto-generated
        $this->assertNotEmpty($payment->payment_no);
        $this->assertStringStartsWith('PAY/', $payment->payment_no);

        $invoice->refresh();

        // Invoice harus otomatis berstatus partial dengan remaining_balance 200rb
        $this->assertEquals(150000, (float) $invoice->paid_amount);
        $this->assertEquals(200000, (float) $invoice->remaining_balance);
        $this->assertEquals('partial', $invoice->status);

        // Bayar sisa 200rb
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 200000,
            'method' => 'transfer',
            'verified_by' => $this->admin->id,
            'paid_at' => now(),
        ]);

        $invoice->refresh();

        // Sekarang invoice harus lunas
        $this->assertEquals(350000, (float) $invoice->paid_amount);
        $this->assertEquals(0, (float) $invoice->remaining_balance);
        $this->assertEquals('paid', $invoice->status);
        $this->assertTrue($invoice->isPaid());
    }

    public function test_payment_deletion_automatically_reverts_invoice_balance(): void
    {
        $invoice = Invoice::create([
            'invoice_no' => 'INV/ZGM/202611/9002',
            'student_id' => $this->student->id,
            'period' => '2026-11',
            'due_date' => now()->addDays(5),
            'amount' => 400000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 400000,
            'status' => 'unpaid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 400000,
            'method' => 'transfer',
            'verified_by' => $this->admin->id,
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, (float) $invoice->remaining_balance);

        // Hapus payment (soft delete)
        $payment->delete();

        $invoice->refresh();
        // Saldo invoice harus kembali belum bayar
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals(400000, (float) $invoice->remaining_balance);
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_check_overdue_command_marks_only_unpaid_invoices(): void
    {
        // Invoice 1: Unpaid dan sudah lewat jatuh tempo -> Harus jadi overdue
        $inv1 = Invoice::create([
            'invoice_no' => 'INV/ZGM/202610/0001',
            'student_id' => $this->student->id,
            'period' => '2026-10',
            'due_date' => now()->subDays(5),
            'amount' => 300000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 300000,
            'status' => 'unpaid',
        ]);

        // Invoice 2: Partial dan sudah lewat jatuh tempo -> Status partial tetap dipertahankan
        $inv2 = Invoice::create([
            'invoice_no' => 'INV/ZGM/202610/0002',
            'student_id' => $this->student->id,
            'period' => '2026-10',
            'due_date' => now()->subDays(5),
            'amount' => 300000,
            'discount' => 0,
            'paid_amount' => 100000,
            'remaining_balance' => 200000,
            'status' => 'partial',
        ]);

        // Invoice 3: Unpaid tetapi jatuh tempo masih di masa depan -> Tetap unpaid
        $inv3 = Invoice::create([
            'invoice_no' => 'INV/ZGM/202611/0003',
            'student_id' => $this->student->id,
            'period' => '2026-11',
            'due_date' => now()->addDays(5),
            'amount' => 300000,
            'discount' => 0,
            'paid_amount' => 0,
            'remaining_balance' => 300000,
            'status' => 'unpaid',
        ]);

        $this->artisan('zigmath:check-overdue')->assertSuccessful();

        $inv1->refresh();
        $inv2->refresh();
        $inv3->refresh();

        $this->assertEquals('overdue', $inv1->status);
        $this->assertEquals('partial', $inv2->status); // Status partial terlindungi
        $this->assertTrue($inv2->isOverdue()); // Helper isOverdue tetap mengenali tanggalnya sudah lewat
        $this->assertEquals('unpaid', $inv3->status);
    }
}

