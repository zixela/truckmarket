<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Services\RatingService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

    public function index(Request $request, RatingService $ratings): View
    {
        $filter = $request->query('filter', 'all');

        $query = $request->user()->reviewsReceived()->visible()->with(['author', 'order.listing']);

        if ($filter === 'positive') {
            $query->where('is_positive', true);
        } elseif ($filter === 'negative') {
            $query->where('is_positive', false);
        }

        return view('account.reviews.index', [
            'reviews' => $query->latest()->paginate(10)->withQueryString(),
            'summary' => $ratings->summary($request->user()),
            'filter' => $filter,
        ]);
    }

    public function create(Order $order): View
    {
        $this->authorize('act', $order);

        return view('account.reviews.create', ['order' => $order->load('listing', 'owner')]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('act', $order);

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'is_positive' => ['required', 'boolean'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->reviews->leave($order, $request->user(), $data['score'], (bool) $data['is_positive'], $data['body']);
        } catch (OrderException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return redirect()
            ->route('account.orders.index')
            ->with('status', __('account.review_saved'));
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->reviews->reply($review, $request->user(), $data['reply']);
        } catch (OrderException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('status', __('account.reply_saved'));
    }
}
