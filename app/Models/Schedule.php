<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Schedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
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
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
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
        return match($this->status) {
            'active' => 'success',
            'cancelled' => 'danger',
            'completed' => 'info',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
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

    /**
     * Get daftar hari dalam format options untuk select
     */
    public static function getDayOptions(): array
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }

    /**
     * Cek apakah jadwal bentrok dengan jadwal lain
     *
     * @param int|null $excludeId ID jadwal yang dikecualikan (saat edit)
     * @return bool True jika bentrok
     */
    public function hasConflict(?int $excludeId = null): bool
    {
        $query = self::query()
            ->where('day_of_week', $this->day_of_week)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // Jadwal baru mulai di tengah jadwal existing
                    $q2->where('start_time', '<', $this->end_time)
                       ->where('end_time', '>', $this->start_time);
                });
            });

        // Cek bentrok untuk siswa yang sama
        $studentConflict = (clone $query)
            ->where('student_id', $this->student_id)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($studentConflict) {
            return true;
        }

        // Cek bentrok untuk tutor yang sama
        $tutorConflict = (clone $query)
            ->where('tutor_name', $this->tutor_name)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        return $tutorConflict;
    }

    /**
     * Get detail bentrok jadwal
     */
    public function getConflictDetails(): ?string
    {
        $conflicts = self::query()
            ->where('day_of_week', $this->day_of_week)
            ->where(function ($q) {
                $q->where('start_time', '<', $this->end_time)
                  ->where('end_time', '>', $this->start_time);
            })
            ->where(function ($q) {
                $q->where('student_id', $this->student_id)
                  ->orWhere('tutor_name', $this->tutor_name);
            })
            ->when($this->id, fn($q) => $q->where('id', '!=', $this->id))
            ->with('student')
            ->get();

        if ($conflicts->isEmpty()) {
            return null;
        }

        $details = [];
        foreach ($conflicts as $conflict) {
            if ($conflict->student_id === $this->student_id) {
                $details[] = "Siswa {$this->student->name} sudah punya jadwal di waktu yang sama";
            }
            if ($conflict->tutor_name === $this->tutor_name) {
                $details[] = "Tutor {$this->tutor_name} sudah mengajar di waktu yang sama";
            }
        }

        return implode('. ', array_unique($details));
    }

    /**
     * Generate jadwal untuk periode tertentu (jika recurring)
     */
    public function generateDatesForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        if (!$this->is_recurring) {
            return [];
        }

        $dates = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($current->dayOfWeekIso === $this->day_of_week) {
                // Cek apakah dalam range start_date - end_date
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
}
