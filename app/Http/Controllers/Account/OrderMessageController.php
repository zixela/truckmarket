<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\OrderMessageMail;
use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderMessageController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        if (! $order->allowsMessages()) {
            return back()->withErrors(['message' => __('orders.chat_closed')]);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = $order->otherParty($request->user());

        $hadUnread = $order->messages()
            ->where('sender_id', '!=', $recipient->id)
            ->whereNull('read_at')
            ->exists();

        $message = $order->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        // Notify only on the first unread message so a burst does not flood the inbox.
        if ($recipient->notify_by_email && ! $hadUnread) {
            Mail::to($recipient->email)
                ->locale($recipient->locale ?: config('app.locale'))
                ->queue(new OrderMessageMail($message));
        }

        return redirect()
            ->route('account.orders.show', $order)
            ->withFragment('latest');
    }

    /** The recipient likes / un-likes a message; own messages cannot be liked. */
    public function toggleLike(Request $request, Order $order, OrderMessage $message): RedirectResponse
    {
        $this->authorize('view', $order);

        abort_if($message->sender_id === $request->user()->id, 403);

        if (! $order->allowsMessages()) {
            return back()->withErrors(['message' => __('orders.chat_closed')]);
        }

        $message->forceFill(['liked_at' => $message->liked_at ? null : now()])->save();

        return redirect()
            ->route('account.orders.show', $order)
            ->withFragment('msg-'.$message->id);
    }
}
