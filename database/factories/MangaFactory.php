<?php

namespace Database\Factories;

use App\Models\Manga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Manga>
 */
class MangaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => (string) fake()->unique()->numberBetween(1, 100000),
            'title' => fake()->sentence(3),
            'original_title' => fake()->optional()->sentence(3),
            'synopsis' => fake()->paragraph(),
            'author' => fake()->name(),
            'status' => fake()->randomElement(['ongoing', 'completed', 'hiatus']),
            'total_volumes' => fake()->numberBetween(1, 100),
            'cover_url' => fake()->imageUrl(600, 900, 'manga', true),
        ];
    }
}
