<?php

namespace App\Models;

/**
 * One site-wide switch: a key and its value, set from the mother app and
 * read here. Asked once per request per key and kept, so a layout that
 * asks twice costs one query.
 */
class AsSiteSetting extends BaseModel
{
    protected $table = 'as_site_settings';

    protected $fillable = ['key', 'value'];

    /** @var array<string, string|null> */
    private static array $memo = [];

    /** The value stored under a key, or the default when there is none. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, self::$memo)) {
            try {
                self::$memo[$key] = static::query()->where('key', $key)->value('value');
            } catch (\Throwable $e) {
                // A deploy that has not run the migration yet: the default.
                return $default;
            }
        }

        return self::$memo[$key] ?? $default;
    }

    /** A yes/no switch, read leniently ("1", "true", "on", "yes"). */
    public static function yes(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }

        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    public static function put(string $key, mixed $value): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        static::query()->updateOrCreate(['key' => $key], ['value' => $stored]);
        self::$memo[$key] = $stored;
    }
}
