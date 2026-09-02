<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'listing_id', 'customer_id', 'owner_id', 'status', 'message', 'response_note',
    'payment_amount', 'payment_status', 'stripe_session_id',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_amount' => 'decimal:2',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function isReviewable(): bool
    {
        return $this->status === OrderStatus::Completed && ! $this->review()->exists();
    }

    /** Messaging is for agreeing on terms — open while the order is pending or confirmed. */
    public function allowsMessages(): bool
    {
        return $this->status->isOpen();
    }

    /** Payment was requested (on confirm) and is still unpaid. */
    public function awaitsPayment(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /** The counterpart of the given user on this order. */
    public function otherParty(User|int $user): User
    {
        $id = $user instanceof User ? $user->id : $user;

        return $id === $this->customer_id ? $this->owner : $this->customer;
    }
}
