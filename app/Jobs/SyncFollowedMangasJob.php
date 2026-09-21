<?php

namespace App\Jobs;

use App\Models\Manga;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncFollowedMangasJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job: refresh volumes of all mangas currently in database/collections.
     */
    public function handle(): void
    {
        Log::info('Starting SyncFollowedMangasJob daily sync');

        $mangas = Manga::all();

        foreach ($mangas as $manga) {
            SyncMangaVolumesJob::dispatch($manga->id);
        }

        Log::info("Dispatched volume sync for {$mangas->count()} mangas");
    }
}
