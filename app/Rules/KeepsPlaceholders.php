<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Every :placeholder of the reference text must survive in the translated text. */
class KeepsPlaceholders implements ValidationRule
{
    public function __construct(private readonly ?string $reference) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return; // empty text falls back to the language file / English
        }

        $missing = array_diff(self::placeholders($this->reference ?? ''), self::placeholders((string) $value));

        if ($missing !== []) {
            $fail('Missing placeholder(s): :'.implode(', :', $missing).'. Keep placeholders exactly as in the English default.');
        }
    }

    /** @return string[] lower-cased placeholder names, e.g. ['email'] for "...code to :email." */
    public static function placeholders(string $text): array
    {
        preg_match_all('/(?<![\w:]):([a-z_]\w*)/i', $text, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }
}
