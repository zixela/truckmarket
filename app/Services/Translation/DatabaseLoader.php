<?php

namespace App\Services\Translation;

use App\Models\Translation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Translation\FileLoader;

/**
 * Layers admin-edited texts (translations table) on top of lang/{locale}/{group}.php.
 * Package namespaces (filament::...) and JSON lines are left untouched.
 */
class DatabaseLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null): array
    {
        $lines = parent::load($locale, $group, $namespace);

        if ($group === '*' || ($namespace !== null && $namespace !== '*')) {
            return $lines;
        }

        foreach ($this->overrides($locale)[$group] ?? [] as $key => $text) {
            Arr::set($lines, $key, $text);
        }

        return $lines;
    }

    /** Language-file lines only, without database overrides. */
    public function fileLines(string $locale, string $group): array
    {
        return parent::load($locale, $group);
    }

    private function overrides(string $locale): array
    {
        try {
            return Translation::forLocale($locale);
        } catch (QueryException) {
            // Table not migrated yet (fresh install): behave like the plain file loader.
            return [];
        }
    }
}
