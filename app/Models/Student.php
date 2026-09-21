<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'class_type', 'package_id', 'parent_name', 'parent_phone',
        'school', 'school_grade', 'address', 'due_day',
        'join_date', 'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'due_day' => 'integer',
        'join_date' => 'date',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'schedule_students')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdueInvoices($query)
    {
        return $query->whereHas('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'overdue', 'partial'])
        );
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->invoices()->sum('paid_amount');
    }

    public function getTotalRemainingAttribute(): float
    {
        return $this->invoices()->sum('remaining_balance');
    }
}
