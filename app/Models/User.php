<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'position',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ==================== FILAMENT INTERFACE ====================

    public function canAccessPanel(Panel $panel): bool
    {
        // ✅ PERBAIKAN: Hapus hasVerifiedEmail() untuk development
        return $this->is_active;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }

    // ==================== RELATIONSHIPS ====================

    public function createdStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    public function updatedStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'updated_by');
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    public function recordedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->role('admin');
    }

    public function scopeFinance($query)
    {
        return $query->role('finance');
    }

    public function scopeOperators($query)
    {
        return $query->role('operator');
    }

    // ==================== ACCESSORS ====================

    public function getInitialsAttribute(): string
    {
        $nameParts = explode(' ', $this->name);
        $initials = '';

        foreach ($nameParts as $part) {
            if (! empty($part)) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        return substr($initials, 0, 2);
    }

    public function getFullNameWithPositionAttribute(): string
    {
        return $this->position
            ? "{$this->name} ({$this->position})"
            : $this->name;
    }

    public function getFormattedPhoneAttribute(): string
    {
        if (empty($this->phone)) {
            return '-';
        }

        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) >= 10) {
            return substr($phone, 0, 4).'-'.substr($phone, 4, 4).'-'.substr($phone, 8);
        }

        return $this->phone;
    }

    // ==================== HELPER METHODS ====================

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super-admin', 'admin']);
    }

    public function isFinance(): bool
    {
        return $this->hasRole(['super-admin', 'admin', 'finance']);
    }

    public function isOperator(): bool
    {
        return $this->hasRole(['super-admin', 'admin', 'operator']);
    }

    public function getRoleNameAttribute(): string
    {
        $role = $this->roles()->first();

        return $role ? $role->name : 'No Role';
    }

    public function getPermissionsArrayAttribute(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    // ✅ HAPUS method hasPermissionTo() karena sudah ada di trait HasRoles

    public function getTotalStudentsManagedAttribute(): int
    {
        return $this->createdStudents()->count();
    }

    public function getTotalPaymentsVerifiedAttribute(): int
    {
        return $this->verifiedPayments()->count();
    }

    public function getTotalIncomeThisMonthAttribute(): float
    {
        return $this->verifiedPayments()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
    }

    // ==================== STATIC METHODS ====================

    public static function getRoleOptions(): array
    {
        return [
            'super-admin' => '👑 Super Admin',
            'admin' => '👨‍💼 Admin',
            'finance' => '💰 Finance',
            'operator' => '🎓 Operator',
        ];
    }

    public static function createDefaultRoles(): void
    {
        // Reset cache permission Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Kumpulkan semua permission unik dari semua role
        $roles = [
            'super-admin' => ['*'],
            'admin' => [
                'view-students', 'create-students', 'edit-students', 'delete-students',
                'view-packages', 'create-packages', 'edit-packages', 'delete-packages',
                'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices',
                'view-payments', 'create-payments', 'edit-payments', 'delete-payments',
                'view-schedules', 'create-schedules', 'edit-schedules', 'delete-schedules',
                'view-attendances', 'create-attendances', 'edit-attendances', 'delete-attendances',
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
                'view-packages',
                'view-schedules', 'create-schedules', 'edit-schedules',
                'view-attendances', 'create-attendances', 'edit-attendances',
            ],
        ];

        // 2. Buat semua permission terlebih dahulu
        $allPermissions = [];
        foreach ($roles as $rolePermissions) {
            if ($rolePermissions === ['*']) {
                continue;
            }

            foreach ($rolePermissions as $permission) {
                if (! in_array($permission, $allPermissions)) {
                    $allPermissions[] = $permission;
                    Permission::firstOrCreate(['name' => $permission]);
                }
            }
        }

        // 3. Buat role dan assign permissions
        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($roleName === 'super-admin') {
                // Super admin dapat SEMUA permission
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($permissions);
            }
        }

        echo "✅ Roles dan permissions berhasil dibuat!\n";
    }
}
