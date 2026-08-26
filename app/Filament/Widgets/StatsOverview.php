<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $thisMonth = now()->format('Y-m');
        $totalIncome = Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $unpaidCount = Invoice::whereIn('status', ['unpaid', 'overdue'])->count();
        $activeRegular = Student::where('status', 'active')->where('class_type', 'regular')->count();
        $activePrivate = Student::where('status', 'active')->where('class_type', 'private')->count();

        return [
            Stat::make('Siswa Aktif', Student::where('status', 'active')->count())
                ->description("📚 {$activeRegular} Reguler • 👤 {$activePrivate} Private")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($totalIncome, 0, ',', '.'))
                ->description('Bulan ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Belum Bayar', $unpaidCount . ' Tagihan')
                ->description($unpaidCount > 0 ? '⚠️ Perlu ditindaklanjuti' : '✅ Semua lunas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unpaidCount > 0 ? 'danger' : 'success'),
        ];
    }
}
