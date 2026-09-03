<?php

namespace App\Services\Translation;

use App\Http\Middleware\SetLocale;
use App\Models\Translation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors lang/en/*.php (+ the RU counterparts) into the translations table.
 *
 * - New keys are inserted with the file text as both value and default.
 * - Existing keys keep admin edits; texts still equal to the old default follow the file.
 * - Keys removed from the files stay in the table.
 */
class TranslationSync
{
    public function __construct(private readonly DatabaseLoader $loader) {}

    /** @return array{created: int, updated: int} */
    public function run(): array
    {
        return DB::transaction(function () {
            $created = $updated = 0;
            $now = now();
            $base = config('app.fallback_locale');

            foreach ($this->groups($base) as $group) {
                $files = [];
                foreach (SetLocale::SUPPORTED as $locale) {
                    $files[$locale] = array_filter(Arr::dot($this->loader->fileLines($locale, $group)), 'is_string');
                }

                $existing = Translation::query()->where('group', $group)->get()->keyBy('key');
                $inserts = [];

                foreach (array_keys($files[$base] ?? []) as $key) {
                    $row = $existing->get($key);
                    $changes = [];

                    foreach (SetLocale::SUPPORTED as $locale) {
                        $text = $files[$locale][$key] ?? null;

                        if (! $row) {
                            $changes[$locale] = $changes["{$locale}_default"] = $text;
                        } elseif ($row->{"{$locale}_default"} !== $text) {
                            if ($row->{$locale} === $row->{"{$locale}_default"}) {
                                $changes[$locale] = $text; // not customized: follow the file
                            }
                            $changes["{$locale}_default"] = $text;
                        }
                    }

                    if (! $row) {
                        $inserts[] = ['group' => $group, 'key' => $key, 'created_at' => $now, 'updated_at' => $now] + $changes;
                        $created++;
                    } elseif ($changes) {
                        $row->forceFill($changes)->saveQuietly();
                        $updated++;
                    }
                }

                foreach (array_chunk($inserts, 500) as $chunk) {
                    Translation::query()->insert($chunk);
                }
            }

            Translation::flushCache();

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /** @return string[] group names = lang/{base}/*.php file names */
    private function groups(string $base): array
    {
        $files = glob(lang_path("{$base}/*.php")) ?: [];

        return array_map(fn (string $file) => pathinfo($file, PATHINFO_FILENAME), $files);
    }
}
