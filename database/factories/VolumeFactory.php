<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Volume>
 */
class VolumeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'number' => (string) fake()->numberBetween(1, 100),
            'title' => fake()->optional()->sentence(2),
            'isbn10' => fake()->optional()->isbn10(),
            'isbn13' => fake()->optional()->isbn13(),
            'publication_date' => fake()->optional()->date(),
            'page_count' => fake()->numberBetween(180, 250),
            'cover_url' => fake()->optional()->imageUrl(600, 900, 'manga-volume', true),
        ];
    }
}
