<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Manga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'manga_id' => Manga::factory(),
            'publisher' => fake()->randomElement(['Glénat', 'Kana', 'Ki-oon', 'Pika', 'Crunchyroll', 'Akata']),
            'language' => 'fr',
            'collection_name' => fake()->optional()->word(),
            'format' => fake()->randomElement(['Standard', 'Double', 'Perfect Edition', 'Collector', 'Poche']),
        ];
    }
}
