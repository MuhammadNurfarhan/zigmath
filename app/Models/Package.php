<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'price', 'duration_months',
        'sessions_count', 'duration_minutes', 'description', 'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_months' => 'integer',
        'sessions_count' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
