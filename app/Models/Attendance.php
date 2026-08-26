<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'student_id',
        'schedule_id',
        'date',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ==================== RELATIONSHIPS ====================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ==================== SCOPES ====================

    public function scopeHadir(Builder $query): Builder
    {
        return $query->where('status', 'hadir');
    }

    public function scopeIzin(Builder $query): Builder
    {
        return $query->where('status', 'izin');
    }

    public function scopeSakit(Builder $query): Builder
    {
        return $query->where('status', 'sakit');
    }

    public function scopeAlpa(Builder $query): Builder
    {
        return $query->where('status', 'alpa');
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->whereMonth('date', $month)
                     ->whereYear('date', $year);
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSchedule(Builder $query, int $scheduleId): Builder
    {
        return $query->where('schedule_id', $scheduleId);
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'hadir' => 'success',
            'izin' => 'warning',
            'sakit' => 'info',
            'alpa' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'hadir' => '✅ Hadir',
            'izin' => '📝 Izin',
            'sakit' => '🤒 Sakit',
            'alpa' => '❌ Alpa',
            default => 'Unknown',
        };
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('d M Y');
    }

    public function getDayNameAttribute(): string
    {
        return $this->date->translatedFormat('l');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Cek apakah absensi ini untuk hari ini
     */
    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    /**
     * Cek apakah absensi ini untuk masa lalu
     */
    public function isPast(): bool
    {
        return $this->date->isPast();
    }

    /**
     * Get warna status untuk UI
     */
    public static function getStatusOptions(): array
    {
        return [
            'hadir' => '✅ Hadir',
            'izin' => '📝 Izin',
            'sakit' => '🤒 Sakit',
            'alpa' => '❌ Alpa',
        ];
    }

    /**
     * Hitung persentase kehadiran siswa dalam periode tertentu
     */
    public static function getAttendanceRate(int $studentId, int $month, int $year): float
    {
        $total = self::forStudent($studentId)
                    ->forMonth($month, $year)
                    ->count();

        if ($total === 0) {
            return 0;
        }

        $hadir = self::forStudent($studentId)
                    ->forMonth($month, $year)
                    ->hadir()
                    ->count();

        return round(($hadir / $total) * 100, 2);
    }
}
