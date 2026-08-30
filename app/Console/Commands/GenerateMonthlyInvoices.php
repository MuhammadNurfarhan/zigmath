<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'zigmath:generate-invoices {--period= : Format YYYY-MM, default bulan ini}';
    protected $description = 'Generate tagihan bulanan otomatis untuk siswa aktif';

    public function handle(): int
    {
        $period = $this->option('period') ?? now()->format('Y-m');
        $currentDate = Carbon::parse("{$period}-01");

        $students = Student::where('status', 'active')->with('package')->get();

        $created = 0;
        $skipped = 0;
        $expired = 0;

        $this->info("🔄 Generating invoices for period: {$period}");
        $bar = $this->output->createProgressBar(count($students));
        $bar->start();

        foreach ($students as $student) {
            if (!$student->package) {
                $expired++;
                $bar->advance();
                continue;
            }

            // Cek duplikat
            $exists = Invoice::where('student_id', $student->id)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Cek masa aktif paket (hanya untuk non-bulanan)
            $duration = (int) ($student->package->duration_months ?? 1);

            if ($duration > 1) {
                $joinDate = $student->join_date
                    ? Carbon::parse($student->join_date)
                    : $student->created_at;
                $activeUntil = $joinDate->copy()->startOfMonth()->addMonths($duration);

                if ($currentDate->gte($activeUntil)) {
                    $expired++;
                    $bar->advance();
                    continue;
                }
            }

            // Generate invoice
            $dueDay = $student->due_day ?? 5;
            $dueDate = $currentDate->copy()->day(min($dueDay, 28));

            $seqNumber = Invoice::where('period', $period)->withTrashed()->count() + 1;
            $invoiceNo = 'INV/ZGM/' . str_replace('-', '', $period) . '/' . str_pad($seqNumber, 4, '0', STR_PAD_LEFT);

            Invoice::create([
                'invoice_no'        => $invoiceNo,
                'student_id'        => $student->id,
                'period'            => $period,
                'due_date'          => $dueDate,
                'amount'            => $student->package->price,
                'discount'          => 0,
                'paid_amount'       => 0,
                'remaining_balance' => $student->package->price,
                'status'            => 'unpaid',
                'created_by'        => null,
            ]);

            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Done!");
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['🆕 Created', $created],
                ['⏭️  Skipped (sudah ada)', $skipped],
                ['⚠️  Skipped (paket habis/no package)', $expired],
            ]
        );

        return self::SUCCESS;
    }
}
