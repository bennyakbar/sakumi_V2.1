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
        'description',
    ];

    private static array $runtimeCache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$runtimeCache[$key])) {
            return self::$runtimeCache[$key];
        }

        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        $value = self::castValue($setting->value, $setting->type);
        self::$runtimeCache[$key] = $value;

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );

        Cache::forget("setting.{$key}");
        unset(self::$runtimeCache[$key]);
    }

    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public static function clearCache(): void
    {
        self::$runtimeCache = [];
    }
}
