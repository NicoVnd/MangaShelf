<?php

namespace App\Console\Commands;

use App\Models\Manga;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportMangasCommand extends Command
{
    protected $signature = 'manga:import
        {--pages=100 : Number of pages to fetch from Jikan (25 manga per page)}
        {--start=1 : Starting page number}
        {--order=popularity : Order by: popularity, score, rank, favorites}
        {--skip-translation : Skip French translation of synopsis (faster import)}';

    protected $description = 'Import manga from the Jikan (MyAnimeList) API into the local database';

    protected int $imported = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected int $errors = 0;

    public function handle(): int
    {
        $totalPages = (int) $this->option('pages');
        $startPage = (int) $this->option('start');
        $orderBy = $this->option('order');
        $skipTranslation = $this->option('skip-translation');

        $this->info('🚀 Starting manga import from Jikan API');
        $this->info("   Pages: {$startPage} to ".($startPage + $totalPages - 1)." ({$totalPages} pages × 25 = ".($totalPages * 25).' max manga)');
        $this->info("   Order: {$orderBy}");
        $this->info('   Translation: '.($skipTranslation ? 'SKIPPED (fast mode)' : 'French via MyMemory'));
        $this->newLine();

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Initializing...');
        $bar->start();

        for ($page = $startPage; $page < $startPage + $totalPages; $page++) {
            $bar->setMessage("Page {$page} — Fetching...");

            $items = null;
            $maxRetries = 5;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $items = $this->fetchPage($page, $orderBy);

                if ($items !== null) {
                    break;
                }

                $waitSeconds = min($attempt * 3, 15);
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

            // Respect Jikan rate limit: ~3 req/sec → sleep 400ms between pages
            usleep(400_000);
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
     * Fetch one page of manga from Jikan API.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function fetchPage(int $page, string $orderBy): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'MangaShelf/1.0',
                ])
                ->timeout(15)
                ->get('https://api.jikan.moe/v4/manga', [
                    'page' => $page,
                    'limit' => 25,
                    'order_by' => $orderBy,
                    'sort' => 'asc',
                    'sfw' => 'true',
                ]);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::warning("Jikan import page {$page} returned status {$response->status()}");
        } catch (\Throwable $e) {
            Log::error("Jikan import page {$page} error: {$e->getMessage()}");
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
        $malId = $data['mal_id'] ?? null;
        if (! $malId) {
            $this->skipped++;

            return;
        }

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

        $synopsis = $data['synopsis'] ?? null;

        // Translate synopsis if not skipped
        if ($synopsis && ! $skipTranslation) {
            try {
                $translator = app(TranslationService::class);
                $synopsis = $translator->translateToFrench($synopsis);
            } catch (\Throwable $e) {
                // Keep original if translation fails
            }
        }

        $attributes = [
            'title' => $data['title'],
            'original_title' => $data['title_japanese'] ?? null,
            'synopsis' => $synopsis,
            'author' => ! empty($authors) ? implode(', ', $authors) : null,
            'status' => isset($data['status']) ? strtolower($data['status']) : null,
            'total_volumes' => $data['volumes'] ?? null,
            'score' => $data['score'] ?? null,
            'genres' => $genres,
            'cover_url' => $coverUrl,
        ];

        $existing = Manga::where('external_id', (string) $malId)->first();

        if ($existing) {
            $existing->update($attributes);
            $this->updated++;
        } else {
            Manga::create(array_merge($attributes, [
                'external_id' => (string) $malId,
            ]));
            $this->imported++;
        }
    }
}
