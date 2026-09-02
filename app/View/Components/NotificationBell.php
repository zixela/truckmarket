<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class NotificationBell extends Component
{
    public int $newOrders = 0;

    public int $unreadMessages = 0;

    public int $total = 0;

    public function __construct()
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->newOrders = Order::query()
            ->where('owner_id', $user->id)
            ->where('status', OrderStatus::Pending)
            ->count();

        $this->unreadMessages = OrderMessage::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereHas('order', fn ($query) => $query
                ->where('owner_id', $user->id)
                ->orWhere('customer_id', $user->id))
            ->count();

        $this->total = $this->newOrders + $this->unreadMessages;
    }

    public function render(): View
    {
        return view('components.notification-bell');
    }
}
