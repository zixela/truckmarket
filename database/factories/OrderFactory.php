<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $listing = Listing::factory();

        return [
            'listing_id' => $listing,
            'customer_id' => User::factory(),
            'owner_id' => fn (array $attributes) => Listing::find($attributes['listing_id'])->user_id,
            'status' => OrderStatus::Pending,
            'message' => fake()->sentence(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'confirmed_at' => in_array($status, [OrderStatus::Confirmed, OrderStatus::Completed], true) ? now() : null,
            'completed_at' => $status === OrderStatus::Completed ? now() : null,
        ]);
    }
}
