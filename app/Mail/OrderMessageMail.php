<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OrderMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('orders.mail.message_subject'));
    }

    public function content(): Content
    {
        $this->message->loadMissing(['sender', 'order.listing']);

        return new Content(
            markdown: 'mail.order-status',
            with: [
                'line' => __('orders.mail.message_line', [
                    'sender' => $this->message->sender->name,
                    'listing' => $this->message->order->listing->title,
                ]),
                'url' => route('account.orders.show', [
                    'locale' => app()->getLocale(),
                    'order' => $this->message->order_id,
                ]),
            ],
        );
    }
}
