<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Order $order): bool
    {
        return in_array($user->id, [$order->customer_id, $order->owner_id], true);
    }

    /** Only the listing owner reacts to incoming orders. */
    public function respond(User $user, Order $order): bool
    {
        return $user->id === $order->owner_id;
    }

    /** Only the customer may cancel or review their own order. */
    public function act(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id;
    }
}
