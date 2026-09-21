<?php

namespace App\Jobs;

use App\Models\Manga;
use App\Services\Manga\MangaImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncMangaVolumesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $mangaId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MangaImporter $importer): void
    {
        $manga = Manga::find($this->mangaId);

        if (! $manga) {
            Log::warning("SyncMangaVolumesJob: Manga with ID {$this->mangaId} not found.");

            return;
        }

        Log::info("Syncing volumes for manga '{$manga->title}' (ID: {$manga->id})");

        $volumes = $importer->importVolumes($manga);

        Log::info('Successfully synced '.count($volumes)." volumes for '{$manga->title}'");
    }
}
