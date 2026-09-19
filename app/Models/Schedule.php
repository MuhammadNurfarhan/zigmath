<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Schedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tutor_name',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'is_recurring',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
        'is_recurring' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Relasi Many-to-Many ke Siswa melalui tabel pivot schedule_students
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'schedule_students')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForDay(Builder $query, int $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope untuk mencari jadwal berdasarkan ID siswa (melalui pivot)
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->whereHas('students', function ($q) use ($studentId) {
            $q->where('students.id', $studentId);
        });
    }

    public function scopeForTutor(Builder $query, string $tutorName): Builder
    {
        return $query->where('tutor_name', $tutorName);
    }

    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('is_recurring', true);
    }

    public function scopeToday(Builder $query): Builder
    {
        $today = Carbon::now()->dayOfWeekIso; // 1=Senin, 7=Minggu

        return $query->where('day_of_week', $today);
    }

    // ==================== ACCESSORS ====================

    public function getDayNameAttribute(): string
    {
        $days = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    public function getTimeRangeAttribute(): string
    {
        return "{$this->start_time} - {$this->end_time}";
    }

    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'cancelled' => 'danger',
            'completed' => 'info',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => '🟢 Aktif',
            'cancelled' => '🔴 Dibatalkan',
            'completed' => '🔵 Selesai',
            default => 'Unknown',
        };
    }

    public function getFullScheduleAttribute(): string
    {
        return "{$this->day_name}, {$this->time_range}";
    }

    // ==================== HELPER METHODS ====================

    public static function getDayOptions(): array
    {
        return [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ];
    }

    /**
     * Cek apakah jadwal bentrok dengan jadwal lain
     *
     * @param  int|null  $excludeId  ID jadwal yang dikecualikan (saat edit)
     * @param  array  $studentIds  Array ID siswa yang akan dicek (opsional, jika relasi belum di-load)
     * @return bool True jika bentrok
     */
    public function hasConflict(?int $excludeId = null, array $studentIds = []): bool
    {
        // Jika studentIds kosong, coba ambil dari relasi yang sudah di-load
        if (empty($studentIds) && $this->relationLoaded('students')) {
            $studentIds = $this->students->pluck('id')->toArray();
        }

        $baseQuery = self::query()
            ->where('day_of_week', $this->day_of_week)
            ->where('start_time', '<', $this->end_time)
            ->where('end_time', '>', $this->start_time)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        // 1. Cek bentrok untuk Tutor yang sama
        $tutorConflict = (clone $baseQuery)
            ->where('tutor_name', $this->tutor_name)
            ->exists();

        if ($tutorConflict) {
            return true;
        }

        // 2. Cek bentrok untuk Siswa (jika ada siswa yang dipilih)
        if (! empty($studentIds)) {
            $studentConflict = (clone $baseQuery)
                ->whereHas('students', function ($q) use ($studentIds) {
                    $q->whereIn('students.id', $studentIds);
                })
                ->exists();

            if ($studentConflict) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detail bentrok jadwal
     */
    public function getConflictDetails(?int $excludeId = null, array $studentIds = []): ?string
    {
        if (empty($studentIds) && $this->relationLoaded('students')) {
            $studentIds = $this->students->pluck('id')->toArray();
        }

        $conflicts = self::query()
            ->where('day_of_week', $this->day_of_week)
            ->where('start_time', '<', $this->end_time)
            ->where('end_time', '>', $this->start_time)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($studentIds) {
                $q->where('tutor_name', $this->tutor_name);
                if (! empty($studentIds)) {
                    $q->orWhereHas('students', function ($subQ) use ($studentIds) {
                        $subQ->whereIn('students.id', $studentIds);
                    });
                }
            })
            ->with('students')
            ->get();

        if ($conflicts->isEmpty()) {
            return null;
        }

        $details = [];
        foreach ($conflicts as $conflict) {
            if ($conflict->tutor_name === $this->tutor_name) {
                $details[] = "Tutor {$this->tutor_name} sudah mengajar di waktu yang sama";
            }

            if (! empty($studentIds)) {
                // Cari irisan (intersect) siswa yang bentrok
                $conflictStudentIds = $conflict->students->pluck('id')->toArray();
                $intersectingIds = array_intersect($studentIds, $conflictStudentIds);

                if (! empty($intersectingIds)) {
                    $conflictStudents = $conflict->students->whereIn('id', $intersectingIds);
                    foreach ($conflictStudents as $cStudent) {
                        $details[] = "Siswa {$cStudent->name} sudah punya jadwal di waktu yang sama";
                    }
                }
            }
        }

        return implode('. ', array_unique($details));
    }

    /**
     * Generate jadwal untuk periode tertentu (jika recurring)
     */
    public function generateDatesForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        if (! $this->is_recurring) {
            return [];
        }

        $dates = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($current->dayOfWeekIso === $this->day_of_week) {
                if ($this->start_date && $current->lt($this->start_date)) {
                    $current->addDay();

                    continue;
                }
                if ($this->end_date && $current->gt($this->end_date)) {
                    break;
                }

                $dates[] = $current->copy();
            }
            $current->addDay();
        }

        return $dates;
    }

    protected static function booted(): void
    {
        static::saving(function (Schedule $schedule) {
            // Cek bentrok Tutor
            $tutorConflict = static::query()
                ->where('id', '!=', $schedule->id ?? 0) // Abaikan diri sendiri saat edit
                ->where('status', 'active')
                ->where('day_of_week', $schedule->day_of_week)
                ->where('tutor_name', $schedule->tutor_name)
                ->whereTime('start_time', '<', $schedule->end_time)
                ->whereTime('end_time', '>', $schedule->start_time)
                ->exists();

            if ($tutorConflict) {
                throw ValidationException::withMessages([
                    'tutor_name' => 'Tutor sudah memiliki jadwal lain pada hari dan jam yang sama.',
                ]);
            }
        });
    }
}
