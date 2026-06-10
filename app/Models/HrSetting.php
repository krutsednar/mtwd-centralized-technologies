<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight key/value settings store for HR forms (leave, and future CTO,
 * Official Business Slip, Pass Slip, etc.). Keys are namespaced by form, e.g.
 * "leave.designated_approver_profile_id". Values are stored as jsonb so a key can
 * hold a scalar (a profile/division id) or a structured value.
 */
class HrSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    private const CACHE_PREFIX = 'hr_setting:';

    /**
     * Read a setting by key, falling back to $default when unset.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key) {
            return static::query()->where('key', $key)->first()?->value;
        });

        return $value ?? $default;
    }

    /**
     * Create or update a setting and bust its cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_PREFIX.$key);
    }
}
