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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->remaining_balance <= 0;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid() && ($this->due_date?->isPast() ?? false);
    }

    public function recalculateBalance(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;
        $netAmount = max(0, (float) $this->amount - (float) $this->discount);
        $this->remaining_balance = max(0, $netAmount - $totalPaid);

        if ($this->remaining_balance <= 0) {
            $this->status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = ($this->due_date && $this->due_date->isPast()) ? 'overdue' : 'unpaid';
        }

        $this->save();
    }

    public static function generateInvoiceNumber(string $period): string
    {
        $maxInvoiceNo = static::withTrashed()
            ->where('period', $period)
            ->max('invoice_no');

        $seqNumber = 1;
        if ($maxInvoiceNo && preg_match('/(\d{4})$/', $maxInvoiceNo, $matches)) {
            $seqNumber = ((int) $matches[1]) + 1;
        }

        $prefix = Setting::getInvoicePrefix();
        return $prefix . str_replace('-', '', $period) . '/' . str_pad($seqNumber, 4, '0', STR_PAD_LEFT);
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
