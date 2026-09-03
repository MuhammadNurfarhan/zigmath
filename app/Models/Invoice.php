<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_no', 'student_id', 'period', 'due_date',
        'amount', 'discount', 'paid_amount', 'remaining_balance',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $invoice) {
            // Auto-generate invoice_no jika kosong
            if (empty($invoice->invoice_no)) {
                $period = $invoice->period ?? now()->format('Y-m');
                $seqNumber = static::where('period', $period)->count() + 1;
                $invoice->invoice_no = 'INV/ZGM/'.str_replace('-', '', $period).'/'.str_pad($seqNumber, 4, '0', STR_PAD_LEFT);
            }

            // Set default paid_amount jika belum di-set
            if ($invoice->paid_amount === null) {
                $invoice->paid_amount = 0;
            }

            // Set remaining_balance = amount - discount - paid_amount
            if ($invoice->remaining_balance === null) {
                $invoice->remaining_balance = ($invoice->amount ?? 0) - ($invoice->discount ?? 0) - ($invoice->paid_amount ?? 0);
            }

            // Set default status
            if (empty($invoice->status)) {
                $invoice->status = 'unpaid';
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPaid(): bool
    {
        return $this->remaining_balance <= 0;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->due_date->isPast();
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial', 'overdue']);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}
