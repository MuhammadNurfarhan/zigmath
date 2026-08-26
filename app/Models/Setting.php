<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    // ==================== CONSTANTS ====================

    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_ARRAY = 'array';

    const GROUP_GENERAL = 'general';
    const GROUP_BRANDING = 'branding';
    const GROUP_FINANCE = 'finance';
    const GROUP_NOTIFICATION = 'notification';
    const GROUP_SYSTEM = 'system';

    // ==================== STATIC METHODS ====================

    /**
     * Get setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "setting_{$key}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->castedValue();
        });
    }

    /**
     * Set setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @return self
     */
    public static function set(string $key, $value, string $type = self::TYPE_STRING, string $group = self::GROUP_GENERAL): self
    {
        $setting = self::firstOrNew(['key' => $key]);

        $setting->value = self::serializeValue($value, $type);
        $setting->type = $type;
        $setting->group = $group;
        $setting->save();

        // Clear cache
        Cache::forget("setting_{$key}");

        return $setting;
    }

    /**
     * Get all settings by group
     *
     * @param string $group
     * @return array
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->castedValue();
        }

        return $result;
    }

    /**
     * Set multiple settings at once
     *
     * @param array $settings ['key' => 'value', ...]
     * @param string $type
     * @param string $group
     * @return void
     */
    public static function setMany(array $settings, string $type = self::TYPE_STRING, string $group = self::GROUP_GENERAL): void
    {
        foreach ($settings as $key => $value) {
            self::set($key, $value, $type, $group);
        }
    }

    /**
     * Delete setting by key
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        Cache::forget("setting_{$key}");
        return self::where('key', $key)->delete();
    }

    /**
     * Clear all settings cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("setting_{$setting->key}");
        }
    }

    // ==================== INSTANCE METHODS ====================

    /**
     * Get casted value based on type
     *
     * @return mixed
     */
    public function castedValue()
    {
        return match($this->type) {
            self::TYPE_NUMBER => (float) $this->value,
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode($this->value, true),
            self::TYPE_ARRAY => explode(',', $this->value),
            default => $this->value,
        };
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    protected static function serializeValue($value, string $type): string
    {
        return match($type) {
            self::TYPE_NUMBER => (string) $value,
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            self::TYPE_JSON => json_encode($value),
            self::TYPE_ARRAY => is_array($value) ? implode(',', $value) : $value,
            default => (string) $value,
        };
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get group options for select
     */
    public static function getGroupOptions(): array
    {
        return [
            self::GROUP_GENERAL => '🏢 Umum',
            self::GROUP_BRANDING => '🎨 Branding',
            self::GROUP_FINANCE => '💰 Keuangan',
            self::GROUP_NOTIFICATION => '🔔 Notifikasi',
            self::GROUP_SYSTEM => '⚙️ Sistem',
        ];
    }

    /**
     * Get type options for select
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_STRING => 'Teks',
            self::TYPE_NUMBER => 'Angka',
            self::TYPE_BOOLEAN => 'Ya/Tidak',
            self::TYPE_JSON => 'JSON',
            self::TYPE_ARRAY => 'Array (comma-separated)',
        ];
    }

    /**
     * Check if setting exists
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    // ==================== COMMON SETTINGS HELPERS ====================

    /**
     * Get bimbel name
     */
    public static function getBimbelName(): string
    {
        return self::get('bimbel_name', 'Zigmath');
    }

    /**
     * Get bimbel logo URL
     */
    public static function getBimbelLogo(): ?string
    {
        return self::get('bimbel_logo');
    }

    /**
     * Get bimbel address
     */
    public static function getBimbelAddress(): string
    {
        return self::get('bimbel_address', '');
    }

    /**
     * Get bimbel phone
     */
    public static function getBimbelPhone(): string
    {
        return self::get('bimbel_phone', '');
    }

    /**
     * Get invoice prefix
     */
    public static function getInvoicePrefix(): string
    {
        return self::get('invoice_prefix', 'INV/ZGM');
    }

    /**
     * Get late fee percentage
     */
    public static function getLateFeePercentage(): float
    {
        return (float) self::get('late_fee_percentage', 0);
    }

    /**
     * Get grace period days
     */
    public static function getGracePeriodDays(): int
    {
        return (int) self::get('grace_period_days', 3);
    }
}
