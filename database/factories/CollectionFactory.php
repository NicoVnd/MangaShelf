<?php

namespace Database\Factories;

use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->words(2, true).' Collection',
            'type' => CollectionType::Personal,
        ];
    }

    /**
     * Indicate that the collection is shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CollectionType::Shared,
        ]);
    }
}
