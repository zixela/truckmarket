<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $score = fake()->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'author_id' => fn (array $attributes) => Order::find($attributes['order_id'])->customer_id,
            'subject_id' => fn (array $attributes) => Order::find($attributes['order_id'])->owner_id,
            'score' => $score,
            'is_positive' => $score >= 3,
            'body' => fake()->sentence(10),
        ];
    }
}
