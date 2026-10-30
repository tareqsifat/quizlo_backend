<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime settings that admins can change without a deploy.
 */
class Setting extends Model
{
    public const ACTIVITY_LOG_INCLUDE_GET = 'activity_log.include_get';

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        // Read on every API request by the logging middleware, so keep it out of the DB.
        $value = Cache::rememberForever(self::cacheKey($key), fn () => static::find($key)?->value);

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return "setting:{$key}";
    }
}
