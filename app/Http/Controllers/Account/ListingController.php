<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\ListingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListingRequest;
use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function __construct(private ListingService $listings) {}

    public function index(Request $request): View
    {
        return view('account.listings.index', [
            'listings' => $request->user()->listings()->with('media')->latest()->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        return view('account.listings.form', [
            'listing' => null,
            'type' => ListingType::tryFrom((string) $request->query('type')) ?? ListingType::Truck,
            'types' => ListingType::cases(),
        ]);
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = ListingType::from($data['type']);

        $listing = $this->listings->create(
            $request->user(),
            $type,
            $data,
            $request->file('photos', [])
        );

        return redirect()
            ->route('account.listings.edit', ['listing' => $listing])
            ->with('status', __('listings.created'));
    }

    public function edit(Listing $listing): View
    {
        $this->authorize('update', $listing);

        $listing->load([$listing->detailRelation(), 'media']);

        return view('account.listings.form', [
            'listing' => $listing,
            'type' => $listing->type,
            'types' => ListingType::cases(),
            'detail' => $listing->detail(),
        ]);
    }

    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $this->listings->update(
            $listing,
            $request->validated(),
            $request->file('photos', []),
            array_map('intval', $request->input('remove_photos', []))
        );

        return back()->with('status', __('listings.updated'));
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        $this->listings->delete($listing);

        return redirect()
            ->route('account.listings.index')
            ->with('status', __('listings.deleted'));
    }
}
