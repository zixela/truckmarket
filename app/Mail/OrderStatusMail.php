<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public OrderStatus $status) {}

    public function envelope(): Envelope
    {
        $key = $this->status === OrderStatus::Pending ? 'created' : $this->status->value;

        return new Envelope(subject: __("orders.mail.{$key}_subject"));
    }

    public function content(): Content
    {
        $key = $this->status === OrderStatus::Pending ? 'created' : $this->status->value;

        return new Content(
            markdown: 'mail.order-status',
            with: [
                'line' => __("orders.mail.{$key}_line", [
                    'customer' => $this->order->customer->name,
                    'owner' => $this->order->owner->name,
                    'listing' => $this->order->listing->title,
                ]),
                'url' => route('account.orders.index', ['locale' => app()->getLocale()]),
            ],
        );
    }
}
