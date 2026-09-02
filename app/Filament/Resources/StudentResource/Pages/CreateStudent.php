<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $student = $this->record;

        if (! $student->package) {
            return;
        }

        // SKIP untuk paket Private (tagihan manual di akhir bulan)
        if ($student->package->type === 'private') {
            return; // Jangan auto-generate invoice untuk private
        }

        // Ambil durasi paket (default 1 bulan jika tidak diset)
        $months = max(1, (int) ($student->package->duration_months ?? 1));
        $start = now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $periodDate = $start->copy()->addMonths($i);
            $period = $periodDate->format('Y-m');

            // Cegah duplikat invoice
            $exists = Invoice::where('student_id', $student->id)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                continue;
            }

            // Jatuh tempo sesuai tgl yang diset siswa (max 28 untuk safety Feb)
            $dueDay = $student->due_day ?? 5;
            $dueDate = $periodDate->copy()->day(min($dueDay, 28));

            // Bulan pertama: jika tgl jatuh tempo sudah lewat, set ke sekarang
            if ($i === 0 && $dueDate->isPast()) {
                $dueDate = now();
            }

            // Generate nomor invoice dengan sequence aman
            $seq = Invoice::where('period', $period)->withTrashed()->count() + 1;
            $invoiceNo = 'INV/ZGM/'.str_replace('-', '', $period).'/'.str_pad($seq, 4, '0', STR_PAD_LEFT);

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
            ]);
        }
    }
}
