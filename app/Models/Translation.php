<?php

namespace App\Models;

use App\Http\Middleware\SetLocale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One site text (group + dotted key) with its EN/RU values and the language-file
 * defaults they were imported from. Values win over lang/*.php; an empty value
 * falls back to the file (and then to English).
 */
#[Fillable(['group', 'key', 'en', 'ru', 'en_default', 'ru_default'])]
class Translation extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Non-empty texts for one locale, grouped: ['common' => ['home' => 'Start', ...], ...].
     *
     * @return array<string, array<string, string>>
     */
    public static function forLocale(string $locale): array
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            return [];
        }

        return Cache::remember("translations:{$locale}", 3600, function () use ($locale) {
            $lines = [];

            static::query()
                ->whereNotNull($locale)
                ->where($locale, '!=', '')
                ->get(['group', 'key', $locale])
                ->each(function (Translation $row) use ($locale, &$lines) {
                    $lines[$row->group][$row->key] = $row->{$locale};
                });

            return $lines;
        });
    }

    public static function flushCache(): void
    {
        foreach (SetLocale::SUPPORTED as $locale) {
            Cache::forget("translations:{$locale}");
        }

        // Drop the translator's in-memory copy so the change is visible in this process too.
        app('translator')->setLoaded([]);
    }

    /** True when the admin changed either text away from the language-file default. */
    public function isCustomized(): bool
    {
        return ($this->en ?? '') !== ($this->en_default ?? '')
            || ($this->ru ?? '') !== ($this->ru_default ?? '');
    }
}
