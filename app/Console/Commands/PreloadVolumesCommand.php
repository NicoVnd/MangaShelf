<?php

namespace App\Console\Commands;

use App\Models\Manga;
use App\Services\Manga\MangaImporter;
use Illuminate\Console\Command;

class PreloadVolumesCommand extends Command
{
    protected $signature = 'manga:preload-volumes
        {--limit=50 : Number of mangas without volumes to process}';

    protected $description = 'Pre-import volumes from OpenLibrary/GoogleBooks into the local database';

    public function handle(MangaImporter $importer): int
    {
        $limit = (int) $this->option('limit');

        // Target mangas that do not have volumes yet, prioritized by score
        $mangas = Manga::whereDoesntHave('volumes')
            ->whereNotNull('total_volumes')
            ->where('total_volumes', '>', 0)
            ->orderBy('score', 'desc')
            ->limit($limit)
            ->get();

        if ($mangas->isEmpty()) {
            $this->info('Tous les mangas ciblés ont déjà leurs tomes en base de données.');

            return self::SUCCESS;
        }

        $this->info("🚀 Préchargement des tomes en BDD pour {$mangas->count()} mangas...");
        $bar = $this->output->createProgressBar($mangas->count());
        $bar->start();

        $importedTotal = 0;
        foreach ($mangas as $manga) {
            try {
                $saved = $importer->importVolumes($manga);
                $importedTotal += count($saved);
            } catch (\Throwable $e) {
                // Continue with next manga
            }

            $bar->advance();
            usleep(250_000); // 250ms pause
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ {$importedTotal} tomes enregistrés en base de données avec succès !");

        return self::SUCCESS;
    }
}
