<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(ListingType::cases()),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(1000, 90000),
            'zip' => fake()->randomElement(['10001', '90001', '60601', '33101', '77001']),
            'latitude' => fake()->latitude(25, 48),
            'longitude' => fake()->longitude(-124, -70),
            'status' => ListingStatus::Active,
        ];
    }

    public function ofType(ListingType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ListingStatus::Inactive]);
    }
}
