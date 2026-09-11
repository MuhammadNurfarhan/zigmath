<?php

namespace App\Filament\Pages;

use App\Models\Student;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class RekapAbsensiPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Rekap Absensi';

    protected static ?string $title = 'Rekap Absensi Bulanan';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.rekap-absensi';

    public int $month;

    public int $year;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function getStudentsProperty(): Collection
    {
        return Student::query()
            ->withCount([
                'attendances as hadir_count' => function ($query) {
                    $query->where('status', 'hadir')
                        ->whereMonth('date', $this->month)
                        ->whereYear('date', $this->year);
                },
                'attendances as izin_count' => function ($query) {
                    $query->where('status', 'izin')
                        ->whereMonth('date', $this->month)
                        ->whereYear('date', $this->year);
                },
                'attendances as sakit_count' => function ($query) {
                    $query->where('status', 'sakit')
                        ->whereMonth('date', $this->month)
                        ->whereYear('date', $this->year);
                },
                'attendances as alpa_count' => function ($query) {
                    $query->where('status', 'alpa')
                        ->whereMonth('date', $this->month)
                        ->whereYear('date', $this->year);
                },
            ])
            ->orderBy('name')
            ->get();
    }

    public function getMonthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function getYearOptions(): array
    {
        $currentYear = now()->year;

        return collect(range($currentYear - 2, $currentYear + 1))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->toArray();
    }
}
