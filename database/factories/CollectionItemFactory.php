<?php

namespace Database\Factories;

use App\Enums\CollectionItemStatus;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionItem>
 */
class CollectionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'volume_id' => Volume::factory(),
            'user_id' => User::factory(),
            'status' => CollectionItemStatus::Owned,
            'purchase_price' => fake()->optional()->randomFloat(2, 6, 25),
            'purchased_at' => fake()->optional()->date(),
            'read_at' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the item is in wishlist.
     */
    public function wishlist(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionItemStatus::Wishlist,
            'purchase_price' => null,
            'purchased_at' => null,
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the item is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionItemStatus::Read,
            'read_at' => fake()->date(),
        ]);
    }
}
