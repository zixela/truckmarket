<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RatingService;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user, RatingService $ratings): View
    {
        $listings = $user->listings()
            ->active()
            ->with('media')
            ->latest()
            ->paginate(12);

        $reviews = $user->reviewsReceived()
            ->visible()
            ->with('author')
            ->latest()
            ->limit(20)
            ->get();

        return view('profile.show', [
            'user' => $user,
            'rating' => $ratings->summary($user),
            'listings' => $listings,
            'reviews' => $reviews,
        ]);
    }
}
