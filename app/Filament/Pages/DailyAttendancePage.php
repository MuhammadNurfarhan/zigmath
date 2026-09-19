<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class DailyAttendancePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Absensi Harian';

    protected static ?string $title = 'Absensi Harian';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.daily-attendance';

    public ?string $date = null;

    public ?int $schedule_id = null;

    public array $attendances = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->schedule_id = null;
        $this->attendances = [];
    }

    public function updatedScheduleId(): void
    {
        $this->loadAttendances();
    }

    public function getSchedulesProperty(): Collection
    {
        if (! $this->date) {
            return collect();
        }

        $day = Carbon::parse($this->date)->dayOfWeekIso;

        return Schedule::with('students')
            ->where('status', 'active')
            ->where('day_of_week', $day)
            ->orderBy('start_time')
            ->get();
    }

    public function getSelectedScheduleProperty(): ?Schedule
    {
        if (! $this->schedule_id) {
            return null;
        }

        return Schedule::with('students')->find($this->schedule_id);
    }

    protected function loadAttendances(): void
    {
        $this->attendances = [];

        if (! $this->selectedSchedule || ! $this->date) {
            return;
        }

        foreach ($this->selectedSchedule->students as $student) {
            $existing = Attendance::where('student_id', $student->id)
                ->where('schedule_id', $this->schedule_id)
                ->whereDate('date', $this->date)
                ->first();

            $this->attendances[$student->id] = [
                'status' => $existing->status ?? 'hadir',
                'notes' => $existing->notes ?? '',
            ];
        }
    }

    public function markAllPresent(): void
    {
        foreach ($this->attendances as $studentId => $attendance) {
            $this->attendances[$studentId]['status'] = 'hadir';
        }
    }

    public function save(): void
    {
        if (! $this->date || ! $this->schedule_id) {
            Notification::make()
                ->title('Pilih tanggal dan jadwal terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        foreach ($this->attendances as $studentId => $attendance) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'schedule_id' => $this->schedule_id,
                    'date' => $this->date,
                ],
                [
                    'status' => $attendance['status'],
                    'notes' => $attendance['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]
            );
        }

        Notification::make()
            ->title('Absensi berhasil disimpan.')
            ->success()
            ->send();
    }
}
