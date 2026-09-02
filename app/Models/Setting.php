<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'description'])]
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget('setting:'.$setting->key));
        static::deleted(fn (Setting $setting) => Cache::forget('setting:'.$setting->key));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember(
            'setting:'.$key,
            300,
            fn () => static::query()->find($key)?->value
        ) ?? $default;
    }

    public static function bool(string $key): bool
    {
        return in_array(static::get($key), ['1', 'true', 'on', 'yes'], true);
    }
}
