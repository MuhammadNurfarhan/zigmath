<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_no', 'invoice_id', 'amount', 'method',
        'reference_number', 'proof_path', 'notes', 'verified_by', 'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->payment_no)) {
                $payment->payment_no = 'PAY/'.now()->format('Ymd').'/'.strtoupper(Str::random(5));
            }
            if (empty($payment->verified_by) && auth()->check()) {
                $payment->verified_by = auth()->id();
            }
        });

        static::saved(function (Payment $payment) {
            $payment->invoice?->recalculateBalance();
        });

        static::deleted(function (Payment $payment) {
            $payment->invoice?->recalculateBalance();
        });

        static::restored(function (Payment $payment) {
            $payment->invoice?->recalculateBalance();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
