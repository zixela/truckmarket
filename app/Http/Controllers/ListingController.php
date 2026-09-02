<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\RatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingController extends Controller
{
    /** SEO detail page: /{locale}/{typeSlug}/{title-slug}-{id} */
    public function show(Request $request, string $typeSlug, string $slugId, RatingService $ratings): View|RedirectResponse
    {
        if (! preg_match('/-(\d+)$/', $slugId, $matches)) {
            throw new NotFoundHttpException;
        }

        $listing = Listing::query()->findOrFail((int) $matches[1]);

        $isOwner = $request->user()?->id === $listing->user_id;

        if ($listing->status !== ListingStatus::Active && ! $isOwner && ! $request->user()?->isAdmin()) {
            throw new NotFoundHttpException;
        }

        // Canonical redirect when type slug or title slug in the URL is stale/wrong.
        $canonical = $listing->seoUrl();

        if ($request->url() !== $canonical) {
            return redirect($canonical, 301);
        }

        $listing->load(['user', 'media', $listing->detailRelation()]);
        $this->countView($listing, $request);

        return view('listings.show', [
            'listing' => $listing,
            'detail' => $listing->detail(),
            'rating' => $ratings->summary($listing->user_id),
            'canOrder' => $request->user() && ! $isOwner,
        ]);
    }

    /** Legacy /listings/{id} — permanent redirect to the SEO URL. */
    public function legacy(Listing $listing): RedirectResponse
    {
        return redirect($listing->seoUrl(), 301);
    }

    /** One view per visitor per listing per hour. */
    private function countView(Listing $listing, Request $request): void
    {
        $key = 'listing:'.$listing->id.':view:'.($request->user()?->id ?? $request->ip());

        if (Cache::add($key, true, 3600)) {
            $listing->increment('views');
        }
    }
}
