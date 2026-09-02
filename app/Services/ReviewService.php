<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;

class ReviewService
{
    public function __construct(private RatingService $ratings) {}

    /**
     * Only the customer of a completed order may review, exactly once.
     *
     * @throws OrderException
     */
    public function leave(Order $order, User $author, int $score, bool $isPositive, string $body): Review
    {
        if ($order->customer_id !== $author->id || $order->status !== OrderStatus::Completed) {
            throw new OrderException(__('account.review_not_allowed'));
        }

        if ($order->review()->exists()) {
            throw new OrderException(__('account.review_exists'));
        }

        $review = Review::query()->create([
            'order_id' => $order->id,
            'author_id' => $author->id,
            'subject_id' => $order->owner_id,
            'score' => $score,
            'is_positive' => $isPositive,
            'body' => $body,
        ]);

        $this->ratings->forget($order->owner_id);

        return $review;
    }

    /** The reviewed user may reply exactly once. */
    public function reply(Review $review, User $user, string $reply): Review
    {
        if ($review->subject_id !== $user->id || $review->reply !== null) {
            throw new OrderException(__('orders.invalid_transition'));
        }

        $review->update(['reply' => $reply, 'replied_at' => now()]);

        return $review;
    }
}
