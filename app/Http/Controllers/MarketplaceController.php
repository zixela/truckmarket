<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Http\Requests\ListingFilterRequest;
use App\Services\ListingCache;
use App\Services\ListingSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    /** SEO page per listing type: /{locale}/trucks, /{locale}/loads, ... */
    public function type(ListingFilterRequest $request, string $typeSlug, ListingSearch $search, ListingCache $cache): View
    {
        $type = ListingType::fromSlug($typeSlug);

        abort_if($type === null, 404);

        $filters = $request->filters();

        return view('marketplace', [
            'types' => ListingType::cases(),
            'counts' => $cache->countsByType(),
            'activeType' => $type,
            'filters' => $filters,
            'listings' => $search->paginate($type, $filters),
        ]);
    }

    /** Legacy /marketplace?type=... — permanent redirect to the SEO URL. */
    public function legacy(ListingFilterRequest $request): RedirectResponse
    {
        $type = ListingType::tryFrom((string) $request->query('type')) ?? ListingType::Truck;

        return redirect()->route('listings.type', array_merge(
            ['typeSlug' => $type->slug()],
            collect($request->filters())->except('type')->all()
        ), 301);
    }
}
