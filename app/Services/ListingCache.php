<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListingType;
use App\Models\Listing;
use Illuminate\Support\Facades\Cache;

class ListingCache
{
    private const COUNTS_KEY = 'listings:counts';

    private const PREVIEW_KEY = 'listings:preview:';

    private const TTL = 600;

    /** @return array<string,int> keyed by ListingType value */
    public function countsByType(): array
    {
        return Cache::remember(self::COUNTS_KEY, self::TTL, function () {
            $counts = Listing::query()->active()
                ->selectRaw('type, COUNT(*) as aggregate')
                ->groupBy('type')
                ->pluck('aggregate', 'type')
                ->all();

            $result = [];
            foreach (ListingType::cases() as $type) {
                $result[$type->value] = (int) ($counts[$type->value] ?? 0);
            }

            return $result;
        });
    }

    /** Latest active listings of a type for the home page preview rows. */
    public function preview(ListingType $type, int $limit = 4)
    {
        $ids = Cache::remember(self::PREVIEW_KEY.$type->value, self::TTL, fn () => Listing::query()
            ->active()
            ->ofType($type)
            ->latest()
            ->limit($limit)
            ->pluck('id')
            ->all());

        if ($ids === []) {
            return collect();
        }

        return Listing::query()
            ->with(['media', 'user'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Listing $listing) => array_search($listing->id, $ids, true))
            ->values();
    }

    public function flush(): void
    {
        Cache::forget(self::COUNTS_KEY);
        foreach (ListingType::cases() as $type) {
            Cache::forget(self::PREVIEW_KEY.$type->value);
        }
    }
}
