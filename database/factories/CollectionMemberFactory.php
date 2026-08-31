<?php

namespace Database\Factories;

use App\Enums\CollectionMemberRole;
use App\Models\Collection;
use App\Models\CollectionMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionMember>
 */
class CollectionMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => CollectionMemberRole::Viewer,
        ];
    }

    /**
     * Indicate that the member has editor role.
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => CollectionMemberRole::Editor,
        ]);
    }

    /**
     * Indicate that the member has owner role.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => CollectionMemberRole::Owner,
        ]);
    }
}
