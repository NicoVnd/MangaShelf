<?php

namespace App\Console\Commands;

use App\Models\Manga;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportMangasAniListCommand extends Command
{
    protected $signature = 'manga:import-anilist
        {--pages=10 : Number of pages to fetch (50 manga per page)}
        {--start=1 : Starting page number}
        {--skip-translation : Skip French translation of synopsis (faster import)}';

    protected $description = 'Import manga from the AniList GraphQL API into the local database';

    protected int $imported = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected int $errors = 0;

    public function handle(): int
    {
        $totalPages = (int) $this->option('pages');
        $startPage = (int) $this->option('start');
        $skipTranslation = (bool) $this->option('skip-translation');

        $this->info('🚀 Starting manga import from AniList GraphQL API');
        $this->info("   Pages: {$startPage} to ".($startPage + $totalPages - 1)." ({$totalPages} pages × 50 = ".($totalPages * 50).' max manga)');
        $this->info('   Translation: '.($skipTranslation ? 'SKIPPED (fast mode)' : 'French via MyMemory'));
        $this->newLine();

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Initializing...');
        $bar->start();

        for ($page = $startPage; $page < $startPage + $totalPages; $page++) {
            $bar->setMessage("Page {$page} — Fetching...");

            $items = null;
            $maxRetries = 3;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $items = $this->fetchPage($page);

                if ($items !== null) {
                    break;
                }

                $waitSeconds = min($attempt * 2, 6);
                $bar->setMessage("Page {$page} — API error (attempt {$attempt}/{$maxRetries}), waiting {$waitSeconds}s...");
                sleep($waitSeconds);
            }

            if ($items === null) {
                $this->errors++;
                $bar->setMessage("Page {$page} — Failed after {$maxRetries} attempts, skipping");
                $bar->advance();

                continue;
            }

            if (empty($items)) {
                $bar->setMessage("Page {$page} — No more results, stopping");
                $bar->advance();

                break;
            }

            foreach ($items as $item) {
                $this->importManga($item, $skipTranslation);
            }

            $bar->setMessage("Page {$page} — {$this->imported} imported, {$this->updated} updated");
            $bar->advance();

            // Respect AniList rate limit: 90 requests/minute -> 700ms pause
            usleep(700_000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Import complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['New manga imported', $this->imported],
                ['Existing manga updated', $this->updated],
                ['Skipped (no data)', $this->skipped],
                ['API errors', $this->errors],
                ['Total manga in DB', Manga::count()],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Fetch one page of manga from AniList GraphQL API.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function fetchPage(int $page): ?array
    {
        $graphql = <<<'GRAPHQL'
        query ($page: Int) {
            Page(page: $page, perPage: 50) {
                media(type: MANGA, sort: POPULARITY_DESC) {
                    id
                    idMal
                    title {
                        romaji
                        english
                        native
                    }
                    description(asHtml: false)
                    volumes
                    status
                    genres
                    averageScore
                    coverImage {
                        extraLarge
                        large
                    }
                    staff(perPage: 3) {
                        edges {
                            role
                            node {
                                name {
                                    full
                                }
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'MangaShelf/1.0',
                ])
                ->timeout(15)
                ->post('https://graphql.anilist.co', [
                    'query' => $graphql,
                    'variables' => [
                        'page' => $page,
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('data.Page.media') ?? [];
            }

            Log::warning("AniList import page {$page} returned status {$response->status()}");
        } catch (\Throwable $e) {
            Log::error("AniList import page {$page} error: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Import a single manga item into the database.
     *
     * @param  array<string, mixed>  $data
     */
    protected function importManga(array $data, bool $skipTranslation): void
    {
        // Use MAL ID if present, otherwise AniList ID prefixed with anilist_
        $externalId = ! empty($data['idMal']) ? (string) $data['idMal'] : 'al_'.$data['id'];

        // Title preference: English first, or Romaji
        $title = $data['title']['english'] ?? $data['title']['romaji'] ?? null;
        if (! $title) {
            $this->skipped++;

            return;
        }

        // Author from staff
        $authors = [];
        foreach ($data['staff']['edges'] ?? [] as $edge) {
            $role = strtolower($edge['role'] ?? '');
            if (str_contains($role, 'story') || str_contains($role, 'art') || str_contains($role, 'original') || str_contains($role, 'author')) {
                $authors[] = $edge['node']['name']['full'] ?? '';
            }
        }
        if (empty($authors) && ! empty($data['staff']['edges'][0]['node']['name']['full'])) {
            $authors[] = $data['staff']['edges'][0]['node']['name']['full'];
        }

        $coverUrl = $data['coverImage']['extraLarge'] ?? $data['coverImage']['large'] ?? null;
        $synopsis = $data['description'] ?? null;

        if ($synopsis) {
            // Strip any leftover HTML tags
            $synopsis = strip_tags(html_entity_decode($synopsis, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (! $skipTranslation) {
                try {
                    $translator = app(TranslationService::class);
                    $synopsis = $translator->translateToFrench($synopsis);
                } catch (\Throwable $e) {
                    // Keep original if translation fails
                }
            }
        }

        $status = match (strtolower($data['status'] ?? '')) {
            'finished' => 'finished',
            'releasing' => 'publishing',
            'not_yet_released' => 'upcoming',
            'hiatus' => 'on_hiatus',
            'cancelled' => 'discontinued',
            default => 'unknown',
        };

        $score = null;
        if (! empty($data['averageScore'])) {
            $score = round($data['averageScore'] / 10, 2); // AniList uses 0-100, convert to 0-10
        }

        $attributes = [
            'title' => $title,
            'original_title' => $data['title']['native'] ?? null,
            'synopsis' => $synopsis,
            'author' => ! empty($authors) ? implode(', ', array_unique(array_filter($authors))) : null,
            'status' => $status,
            'total_volumes' => $data['volumes'] ?? null,
            'score' => $score,
            'genres' => $data['genres'] ?? [],
            'cover_url' => $coverUrl,
        ];

        $existing = Manga::where('external_id', $externalId)->first();

        if ($existing) {
            $existing->update($attributes);
            $this->updated++;
        } else {
            Manga::create(array_merge($attributes, [
                'external_id' => $externalId,
            ]));
            $this->imported++;
        }
    }
}
