<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Filament\Widgets\Widget;

class TodaySchedulesWidget extends Widget
{
    protected static string $view = 'filament.widgets.today-schedules';

    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        $today = now()->dayOfWeekIso;

        $schedules = Schedule::with('student')
            ->where('status', 'active')
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->limit(8)
            ->get();

        return [
            'schedules' => $schedules,
        ];
    }
}
