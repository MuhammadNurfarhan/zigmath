<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class IncomeChart extends ChartWidget
{
    protected static ?string $heading = '📈 Grafik Pemasukan 6 Bulan Terakhir';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $total = Payment::whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount');

            $data[] = (float) $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan (Rp)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }",
                    ],
                ],
            ],
        ];
    }
}
