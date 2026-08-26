<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'position',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ==================== FILAMENT INTERFACE ====================

    /**
     * Determine if the user can access the admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasVerifiedEmail();
    }

    /**
     * Get the user's avatar URL for Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Students created by this user
     */
    public function createdStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    /**
     * Students updated by this user
     */
    public function updatedStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'updated_by');
    }

    /**
     * Payments verified by this user
     */
    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    /**
     * Attendances recorded by this user
     */
    public function recordedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    /**
     * Invoices created by this user
     */
    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    /**
     * Notifications for this user
     */
    // public function notifications(): HasMany
    // {
    //     return $this->hasMany(AppNotification::class);
    // }

    // ==================== SCOPES ====================

    /**
     * Scope to only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only admins
     */
    public function scopeAdmins($query)
    {
        return $query->role('admin');
    }

    /**
     * Scope to only finance staff
     */
    public function scopeFinance($query)
    {
        return $query->role('finance');
    }

    /**
     * Scope to only operators
     */
    public function scopeOperators($query)
    {
        return $query->role('operator');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get user's initials for avatar fallback
     */
    public function getInitialsAttribute(): string
    {
        $nameParts = explode(' ', $this->name);
        $initials = '';

        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        return substr($initials, 0, 2);
    }

    /**
     * Get user's full name with position
     */
    public function getFullNameWithPositionAttribute(): string
    {
        return $this->position
            ? "{$this->name} ({$this->position})"
            : $this->name;
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (empty($this->phone)) {
            return '-';
        }

        // Format: 08xx-xxxx-xxxx
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 11) {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        }
        if (strlen($phone) === 12) {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        }

        return $this->phone;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['super-admin', 'admin']);
    }

    /**
     * Check if user is finance
     */
    public function isFinance(): bool
    {
        return $this->hasRole(['super-admin', 'admin', 'finance']);
    }

    /**
     * Check if user is operator
     */
    public function isOperator(): bool
    {
        return $this->hasRole(['super-admin', 'admin', 'operator']);
    }

    /**
     * Get user's role name
     */
    public function getRoleNameAttribute(): string
    {
        $role = $this->roles()->first();
        return $role ? $role->name : 'No Role';
    }

    /**
     * Get user's permissions as array
     */
    public function getPermissionsArrayAttribute(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermissionTo($permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    /**
     * Get total students managed by this user
     */
    public function getTotalStudentsManagedAttribute(): int
    {
        return $this->createdStudents()->count();
    }

    /**
     * Get total payments verified by this user
     */
    public function getTotalPaymentsVerifiedAttribute(): int
    {
        return $this->verifiedPayments()->count();
    }

    /**
     * Get total income verified by this user (this month)
     */
    public function getTotalIncomeThisMonthAttribute(): float
    {
        return $this->verifiedPayments()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
    }

    // ==================== STATIC METHODS ====================

    /**
     * Get role options for select
     */
    public static function getRoleOptions(): array
    {
        return [
            'super-admin' => '👑 Super Admin',
            'admin' => '👨‍💼 Admin',
            'finance' => '💰 Finance',
            'operator' => '🎓 Operator',
        ];
    }

    /**
     * Create default roles and permissions
     */
    public static function createDefaultRoles(): void
    {
        $roles = [
            'super-admin' => ['*'], // All permissions
            'admin' => [
                'view-students', 'create-students', 'edit-students', 'delete-students',
                'view-packages', 'create-packages', 'edit-packages', 'delete-packages',
                'view-invoices', 'create-invoices', 'edit-invoices',
                'view-payments', 'create-payments', 'edit-payments',
                'view-schedules', 'create-schedules', 'edit-schedules', 'delete-schedules',
                'view-attendances', 'create-attendances', 'edit-attendances',
                'view-reports', 'export-reports',
                'view-settings', 'edit-settings',
            ],
            'finance' => [
                'view-students',
                'view-invoices', 'create-invoices', 'edit-invoices',
                'view-payments', 'create-payments', 'edit-payments',
                'view-reports', 'export-reports',
            ],
            'operator' => [
                'view-students', 'create-students', 'edit-students',
                'view-schedules', 'create-schedules', 'edit-schedules',
                'view-attendances', 'create-attendances', 'edit-attendances',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);

            if ($roleName === 'super-admin') {
                // Super admin gets all permissions
                $allPermissions = \Spatie\Permission\Models\Permission::all();
                $role->syncPermissions($allPermissions);
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
