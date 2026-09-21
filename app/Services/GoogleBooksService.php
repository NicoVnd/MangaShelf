<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBooksService
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_books.key');
    }

    /**
     * Search French manga volumes and upcoming releases for a series.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchMangaVolumes(string $title, int $maxResults = 40): array
    {
        $cacheKey = 'gb_volumes_v2_'.md5(strtolower(trim($title))."_{$maxResults}");

        return Cache::remember($cacheKey, now()->addDays(3), function () use ($title, $maxResults) {
            try {
                $params = [
                    'q' => "{$title} manga",
                    'langRestrict' => 'fr',
                    'orderBy' => 'newest',
                    'maxResults' => min($maxResults, 40),
                    'printType' => 'books',
                ];

                if ($this->apiKey) {
                    $params['key'] = $this->apiKey;
                }

                $response = Http::withoutVerifying()
                    ->timeout(4)
                    ->get('https://www.googleapis.com/books/v1/volumes', $params);

                if ($response->successful()) {
                    $items = $response->json('items') ?? [];

                    return $this->formatItems($items, $title);
                }

                Log::warning("GoogleBooks API status {$response->status()}: {$response->body()}");
            } catch (\Throwable $e) {
                Log::error('GoogleBooks API error: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Format Google Books items into standard structured volume data.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function formatItems(array $items, string $searchedTitle): array
    {
        $volumes = [];
        $now = now();

        foreach ($items as $item) {
            $info = $item['volumeInfo'] ?? [];
            $title = $info['title'] ?? '';

            // Extract volume number
            $number = null;
            if (preg_match('/(?:tome|vol(?:ume)?\.?|#|\bT\.?)\s*(\d+(?:\.\d+)?)/i', $title, $matches)) {
                $number = $matches[1];
            } elseif (preg_match('/\b(\d+)\b/', $title, $matches)) {
                $number = $matches[1];
            }

            // Extract ISBNs
            $isbn13 = null;
            $isbn10 = null;
            foreach ($info['industryIdentifiers'] ?? [] as $id) {
                if (($id['type'] ?? '') === 'ISBN_13') {
                    $isbn13 = $id['identifier'];
                } elseif (($id['type'] ?? '') === 'ISBN_10') {
                    $isbn10 = $id['identifier'];
                }
            }

            // Publication date & Status
            $publishedDate = $info['publishedDate'] ?? null;
            $status = 'released';

            if ($publishedDate) {
                try {
                    $parsed = Carbon::parse($publishedDate);
                    if ($parsed->isFuture()) {
                        $status = 'upcoming';
                    }
                } catch (\Throwable $e) {
                    // Ignore date parsing errors
                }
            }

            $coverUrl = $info['imageLinks']['thumbnail']
                ?? $info['imageLinks']['smallThumbnail']
                ?? null;

            if ($coverUrl && str_starts_with($coverUrl, 'http://')) {
                $coverUrl = str_replace('http://', 'https://', $coverUrl);
            }

            $volumes[] = [
                'number' => $number,
                'title' => $title,
                'publisher' => $info['publisher'] ?? null,
                'publication_date' => $publishedDate,
                'page_count' => $info['pageCount'] ?? null,
                'isbn13' => $isbn13,
                'isbn10' => $isbn10,
                'cover_url' => $coverUrl,
                'status' => $status,
            ];
        }

        return $volumes;
    }
}
