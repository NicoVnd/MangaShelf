<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenLibraryService
{
    /**
     * Search French editions and volumes from Open Library.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchMangaVolumes(string $title, ?string $publisher = null, int $limit = 250): array
    {
        $cacheKey = 'ol_volumes_v7_'.md5(strtolower(trim($title)).'_'.$publisher.'_'.$limit);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($title, $publisher, $limit) {
            try {
                $params = [
                    'title' => $title,
                    'language' => 'fre',
                    'limit' => $limit,
                ];

                if ($publisher) {
                    $params['publisher'] = $publisher;
                }

                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'MangaShelf/1.0 (contact@mangashelf.app)',
                    ])
                    ->timeout(5)
                    ->get('https://openlibrary.org/search.json', $params);

                if ($response->successful()) {
                    $docs = $response->json('docs') ?? [];

                    return $this->formatVolumes($docs);
                }

                Log::warning("OpenLibrary API failed with status {$response->status()}");
            } catch (\Throwable $e) {
                Log::error('OpenLibrary API error: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Get complete list of volumes combining Open Library + Google Books + Jikan metadata.
     *
     * @param  array<string, mixed>  $manga
     * @return array<int, array<string, mixed>>
     */
    public function getVolumesForManga(array $manga, ?GoogleBooksService $googleBooks = null): array
    {
        $title = $manga['title'];

        // 1. Fetch Open Library records
        $olVolumes = $this->searchMangaVolumes($title, limit: 250);

        // 2. Fetch Google Books records if service provided
        $gbVolumes = [];
        if ($googleBooks) {
            $gbVolumes = $googleBooks->searchMangaVolumes($title, 40);
        }

        // Map by volume number
        $volumesByNumber = [];
        $detectedPublisher = null;

        // Populate from Open Library — only reject chapters and spin-offs by title keyword
        foreach ($olVolumes as $olVol) {
            if ($this->isChapterOrSpinoff($olVol['title'] ?? '')) {
                continue;
            }

            $num = $olVol['number'];
            if ($num && is_numeric($num)) {
                $numInt = (int) $num;
                if ($numInt > 0 && $numInt < 300) {
                    if (! isset($volumesByNumber[$numInt])) {
                        $volumesByNumber[$numInt] = $olVol;
                    } elseif (! empty($olVol['cover_url']) && empty($volumesByNumber[$numInt]['cover_url'])) {
                        // Prefer entry with a cover
                        $volumesByNumber[$numInt] = $olVol;
                    } elseif (! empty($olVol['isbn13']) && empty($volumesByNumber[$numInt]['isbn13'])) {
                        $volumesByNumber[$numInt] = $olVol;
                    }
                    if (! $detectedPublisher && ! empty($olVol['publisher'])) {
                        $detectedPublisher = $olVol['publisher'];
                    }
                }
            }
        }

        // Merge/Enrich from Google Books
        foreach ($gbVolumes as $gbVol) {
            if ($this->isChapterOrSpinoff($gbVol['title'] ?? '')) {
                continue;
            }

            $num = $gbVol['number'];
            if ($num && is_numeric($num)) {
                $numInt = (int) $num;
                if ($numInt > 0 && $numInt < 300) {
                    if (! isset($volumesByNumber[$numInt])) {
                        $volumesByNumber[$numInt] = $gbVol;
                    } else {
                        if (empty($volumesByNumber[$numInt]['isbn13']) && ! empty($gbVol['isbn13'])) {
                            $volumesByNumber[$numInt]['isbn13'] = $gbVol['isbn13'];
                        }
                        if (empty($volumesByNumber[$numInt]['cover_url']) && ! empty($gbVol['cover_url'])) {
                            $volumesByNumber[$numInt]['cover_url'] = $gbVol['cover_url'];
                        }
                        if (isset($gbVol['status']) && $gbVol['status'] === 'upcoming') {
                            $volumesByNumber[$numInt]['status'] = 'upcoming';
                            if (! empty($gbVol['publication_date'])) {
                                $volumesByNumber[$numInt]['publication_date'] = $gbVol['publication_date'];
                            }
                        }
                    }
                    if (! $detectedPublisher && ! empty($gbVol['publisher'])) {
                        $detectedPublisher = $gbVol['publisher'];
                    }
                }
            }
        }

        // Determine the real volume count:
        // - Use Jikan total_volumes if available (finished series)
        // - Otherwise use the max found across all sources
        $totalOfficial = $manga['total_volumes'] ?? null;
        $maxIdentified = ! empty($volumesByNumber) ? max(array_keys($volumesByNumber)) : 0;
        $maxVolumeCount = max($maxIdentified, (int) $totalOfficial, 1);

        if ($maxVolumeCount <= 1 && empty($volumesByNumber)) {
            return [];
        }

        $seriesCover = $manga['cover_url'] ?? null;
        $volumes = [];

        for ($v = 1; $v <= $maxVolumeCount; $v++) {
            $matched = $volumesByNumber[$v] ?? null;
            $pubDate = $matched['publication_date'] ?? null;
            $statusVolume = $matched['status'] ?? 'released';

            if ($pubDate && $statusVolume !== 'upcoming') {
                try {
                    $parsed = Carbon::parse($pubDate);
                    if ($parsed->isFuture()) {
                        $statusVolume = 'upcoming';
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            }

            // Use per-volume cover if available, otherwise fall back to series cover
            $coverUrl = ! empty($matched['cover_url']) ? $matched['cover_url'] : $seriesCover;

            $volumes[] = [
                'number' => (string) $v,
                'title' => $matched['title'] ?? "{$title} - Tome {$v}",
                'publisher' => $matched['publisher'] ?? $detectedPublisher ?? null,
                'publication_date' => $pubDate,
                'isbn13' => $matched['isbn13'] ?? null,
                'isbn10' => $matched['isbn10'] ?? null,
                'page_count' => $matched['page_count'] ?? null,
                'cover_url' => $coverUrl,
                'status' => $statusVolume,
            ];
        }

        return $volumes;
    }

    /**
     * Check if a title is a chapter or spin-off (not a real volume).
     */
    protected function isChapterOrSpinoff(string $title): bool
    {
        $lower = Str::lower($title);

        return Str::contains($lower, [
            'chapitre',
            'chapter',
            'light novel',
            'roman',
            'anime comics',
        ]);
    }

    /**
     * Get book metadata by ISBN.
     *
     * @return array<string, mixed>|null
     */
    public function getByIsbn(string $isbn): ?array
    {
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
        $cacheKey = 'ol_isbn_'.$cleanIsbn;

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($cleanIsbn) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'MangaShelf/1.0 (contact@mangashelf.app)',
                    ])
                    ->timeout(12)
                    ->get("https://openlibrary.org/isbn/{$cleanIsbn}.json");

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                Log::error("OpenLibrary ISBN error for {$cleanIsbn}: ".$e->getMessage());
            }

            return null;
        });
    }

    /**
     * Format OpenLibrary documents into a structured list of volumes.
     *
     * @param  array<int, array<string, mixed>>  $docs
     * @return array<int, array<string, mixed>>
     */
    protected function formatVolumes(array $docs): array
    {
        $volumes = [];

        foreach ($docs as $doc) {
            $title = $doc['title'] ?? '';

            // Extract volume number
            // 1. Prefer explicit "Tome X", "Vol X", "T.X" patterns
            // 2. Fallback to a trailing number at the end of the title (e.g. "ONE PIECE 14")
            $number = null;
            if (preg_match('/(?:tome|vol(?:ume)?\.?|#|\bT\.?)\s*(\d+)/i', $title, $matches)) {
                $number = $matches[1];
            } elseif (preg_match('/\s(\d{1,3})\s*$/i', $title, $matches)) {
                $number = $matches[1];
            }

            $coverId = $doc['cover_i'] ?? null;
            $coverUrl = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : null;

            $isbns = $doc['isbn'] ?? [];
            $isbn13 = null;
            $isbn10 = null;

            foreach ($isbns as $isbn) {
                if (strlen($isbn) === 13 && ! $isbn13) {
                    $isbn13 = $isbn;
                } elseif (strlen($isbn) === 10 && ! $isbn10) {
                    $isbn10 = $isbn;
                }
            }

            $publishDates = $doc['publish_date'] ?? [];
            $publishDate = ! empty($publishDates) ? $publishDates[0] : null;

            $publishers = $doc['publisher'] ?? [];
            $publisher = ! empty($publishers) ? $publishers[0] : null;

            $volumes[] = [
                'title' => $title,
                'number' => $number,
                'publisher' => $publisher,
                'publication_date' => $publishDate,
                'page_count' => $doc['number_of_pages_median'] ?? null,
                'isbn10' => $isbn10,
                'isbn13' => $isbn13,
                'cover_url' => $coverUrl,
            ];
        }

        return $volumes;
    }
}
