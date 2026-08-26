<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'zigmath:generate-invoices {--period= : Format YYYY-MM, default bulan ini}';
    protected $description = 'Generate tagihan bulanan otomatis untuk semua siswa aktif';

    public function handle(): int
    {
        $period = $this->option('period') ?? now()->format('Y-m');
        $students = Student::where('status', 'active')->get();
        $created = 0;
        $skipped = 0;

        $this->info("🔄 Generating invoices for period: {$period}");
        $bar = $this->output->createProgressBar(count($students));
        $bar->start();

        foreach ($students as $student) {
            // Cek duplikat
            $exists = Invoice::where('student_id', $student->id)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $dueDate = Carbon::parse("{$period}-01")->day(
                min($student->due_day, 28) // Safety: max 28
            );

            $seqNumber = Invoice::where('period', $period)->count() + 1;
            $invoiceNo = 'INV/ZGM/' . str_replace('-', '', $period) . '/' . str_pad($seqNumber, 4, '0', STR_PAD_LEFT);

            Invoice::create([
                'invoice_no' => $invoiceNo,
                'student_id' => $student->id,
                'period' => $period,
                'due_date' => $dueDate,
                'amount' => $student->package->price,
                'discount' => 0,
                'paid_amount' => 0,
                'remaining_balance' => $student->package->price,
                'status' => 'unpaid',
                'created_by' => null, // System generated
            ]);

            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Done! Created: {$created}, Skipped (already exists): {$skipped}");

        return self::SUCCESS;
    }
}
