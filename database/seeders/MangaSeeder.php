<?php

namespace Database\Seeders;

use App\Models\Manga;
use App\Services\JikanService;
use Illuminate\Database\Seeder;

class MangaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reflection = new \ReflectionClass(JikanService::class);
        $property = $reflection->getProperty('fallbackPopular');
        $property->setAccessible(true);
        $mangas = $property->getValue(app(JikanService::class));

        foreach ($mangas as $item) {
            Manga::updateOrCreate(
                ['external_id' => (string) $item['external_id']],
                [
                    'title' => $item['title'],
                    'original_title' => $item['original_title'] ?? null,
                    'synopsis' => $item['synopsis'] ?? null,
                    'author' => $item['author'] ?? null,
                    'genres' => $item['genres'] ?? [],
                    'status' => $item['status'] ?? 'unknown',
                    'total_volumes' => $item['total_volumes'] ?? null,
                    'score' => $item['score'] ?? null,
                    'cover_url' => $item['cover_url'] ?? null,
                ]
            );
        }
    }
}
