<?php

namespace App\Services\Manga;

use Illuminate\Support\Carbon;

class MangaMapper
{
    /**
     * Map Jikan raw or formatted data to Manga model attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function toMangaAttributes(array $data): array
    {
        // Handle raw Jikan payload or already formatted array
        if (isset($data['mal_id'])) {
            $authors = [];
            foreach ($data['authors'] ?? [] as $author) {
                $authors[] = $author['name'] ?? '';
            }

            $genres = [];
            foreach ($data['genres'] ?? [] as $genre) {
                $genres[] = $genre['name'] ?? '';
            }

            $coverUrl = $data['images']['webp']['large_image_url']
                ?? $data['images']['jpg']['large_image_url']
                ?? $data['images']['jpg']['image_url']
                ?? null;

            return [
                'external_id' => (string) $data['mal_id'],
                'title' => $data['title'],
                'original_title' => $data['title_japanese'] ?? null,
                'synopsis' => $data['synopsis'] ?? null,
                'author' => ! empty($authors) ? implode(', ', $authors) : null,
                'status' => isset($data['status']) ? strtolower($data['status']) : null,
                'total_volumes' => $data['volumes'] ?? null,
                'score' => $data['score'] ?? null,
                'genres' => $genres,
                'cover_url' => $coverUrl,
            ];
        }

        return [
            'external_id' => (string) ($data['external_id'] ?? ''),
            'title' => $data['title'] ?? '',
            'original_title' => $data['original_title'] ?? null,
            'synopsis' => $data['synopsis'] ?? null,
            'author' => $data['author'] ?? null,
            'status' => $data['status'] ?? null,
            'total_volumes' => $data['total_volumes'] ?? null,
            'score' => $data['score'] ?? null,
            'genres' => $data['genres'] ?? [],
            'cover_url' => $data['cover_url'] ?? null,
        ];
    }

    /**
     * Map raw volume array to Volume model attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function toVolumeAttributes(array $data, int $editionId): array
    {
        $pubDate = null;
        if (! empty($data['publication_date'])) {
            try {
                $pubDate = Carbon::parse($data['publication_date'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $pubDate = null;
            }
        }

        return [
            'edition_id' => $editionId,
            'number' => (string) ($data['number'] ?? '1'),
            'title' => $data['title'] ?? null,
            'isbn10' => $data['isbn10'] ?? null,
            'isbn13' => $data['isbn13'] ?? null,
            'publication_date' => $pubDate,
            'page_count' => isset($data['page_count']) ? (int) $data['page_count'] : null,
            'cover_url' => $data['cover_url'] ?? null,
        ];
    }
}
