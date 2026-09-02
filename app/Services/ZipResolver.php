<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ZipCode;
use Illuminate\Support\Facades\Cache;

class ZipResolver
{
    /** @return array{lat: float, lng: float}|null */
    public function coordinates(?string $zip): ?array
    {
        $zip = trim((string) $zip);

        if ($zip === '') {
            return null;
        }

        return Cache::remember("zip:{$zip}", 86400, function () use ($zip) {
            $row = ZipCode::find($zip);

            return $row ? ['lat' => $row->latitude, 'lng' => $row->longitude] : null;
        });
    }
}
