<?php

namespace App\Jobs;

use App\Services\Manga\MangaImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportMangaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int|string $malId,
        public bool $syncVolumesImmediately = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MangaImporter $importer): void
    {
        Log::info("Executing ImportMangaJob for MAL ID {$this->malId}");

        $manga = $importer->importManga($this->malId);

        if ($manga && $this->syncVolumesImmediately) {
            SyncMangaVolumesJob::dispatch($manga->id);
        }
    }
}
