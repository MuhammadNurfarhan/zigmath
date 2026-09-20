<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Models\Invoice;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Console\Command;

class GenerateNotifications extends Command
{
    protected $signature = 'zigmath:generate-notifications';

    protected $description = 'Generate notifikasi internal untuk admin Zigmath';

    public function handle(): int
    {
        $this->info('🔄 Generating notifikasi...');

        $this->generatePaymentDueNotifications();
        $this->generateOverdueNotifications();
        $this->generateScheduleTodayNotifications();
        $this->generateIncompleteDataNotifications();

        $this->info('✅ Notifikasi berhasil digenerate.');

        return self::SUCCESS;
    }

    protected function generatePaymentDueNotifications(): void
    {
        $invoices = Invoice::with('student')
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereBetween('due_date', [
                now()->startOfDay(),
                now()->addDays(3)->endOfDay(),
            ])
            ->get();

        foreach ($invoices as $invoice) {
            $key = 'payment-due-'.$invoice->id.'-'.$invoice->due_date->format('Ymd');

            AppNotification::createNotification(
                type: 'payment_due',
                key: $key,
                title: 'Tagihan Segera Jatuh Tempo',
                message: 'Tagihan '.$invoice->invoice_no.' atas nama '.($invoice->student->name ?? 'Siswa').
                    ' jatuh tempo pada '.$invoice->due_date->format('d M Y').
                    '. Sisa tagihan Rp '.number_format($invoice->remaining_balance, 0, ',', '.'),
                link: '/admin/invoices',
            );
        }
    }

    protected function generateOverdueNotifications(): void
    {
        $invoices = Invoice::with('student')
            ->where('status', 'overdue')
            ->where('remaining_balance', '>', 0)
            ->get();

        foreach ($invoices as $invoice) {
            $key = 'overdue-'.$invoice->id.'-'.$invoice->period;

            AppNotification::createNotification(
                type: 'overdue',
                key: $key,
                title: 'Tagihan Menunggak',
                message: 'Tagihan '.$invoice->invoice_no.' atas nama '.($invoice->student->name ?? 'Siswa').
                    ' sudah melewati jatuh tempo. Sisa tagihan Rp '.number_format($invoice->remaining_balance, 0, ',', '.'),
                link: '/admin/invoices',
            );
        }
    }

    protected function generateScheduleTodayNotifications(): void
    {
        $today = now()->dayOfWeekIso;

        $schedules = Schedule::with('subject')
            ->where('status', 'active')
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        foreach ($schedules as $schedule) {
            $key = 'schedule-today-'.$schedule->id.'-'.now()->format('Ymd');

            $subject = $schedule->subject->name ?? 'Mata pelajaran belum diatur';

            AppNotification::createNotification(
                type: 'schedule_today',
                key: $key,
                title: 'Jadwal Hari Ini',
                message: 'Mata pelajaran '.$subject.
                    ' bersama tutor '.$schedule->tutor_name.
                    ' pada '.$schedule->start_time.' - '.$schedule->end_time,
                link: '/admin/schedules',
            );
        }
    }

    protected function generateIncompleteDataNotifications(): void
    {
        $students = Student::query()
            ->where('status', 'active')
            ->get(['id', 'name', 'parent_name', 'parent_phone', 'address']);

        $incompleteKeys = [];

        foreach ($students as $student) {
            $isIncomplete = blank($student->parent_name)
            || blank($student->parent_phone)
            || blank($student->address);

            if (! $isIncomplete) {
                continue;
            }

            $key = 'incomplete-student-'.$student->id;

            $incompleteKeys[] = $key;

            AppNotification::createNotification(
                type: 'incomplete_data',
                key: $key,
                title: 'Data Siswa Belum Lengkap',
                message: 'Data siswa '.$student->name.' belum lengkap. Mohon lengkapi nama orang tua/wali, nomor HP orang tua, atau alamat.',
                link: '/admin/students/'.$student->id.'/edit',
            );
        }

        // Hapus notifikasi siswa yang datanya sudah lengkap,
        // sudah nonaktif, atau sudah tidak relevan.
        $notificationQuery = AppNotification::where('type', 'incomplete_data')
            ->where('key', 'like', 'incomplete-student-%');

        if (empty($incompleteKeys)) {
            $notificationQuery->delete();
        } else {
            $notificationQuery->whereNotIn('key', $incompleteKeys)->delete();
        }
    }
}
