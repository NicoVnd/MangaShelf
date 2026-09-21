<?php

namespace App\Console\Commands;

use App\Models\Manga;
use App\Services\TranslationService;
use Illuminate\Console\Command;

class TranslateMangasCommand extends Command
{
    protected $signature = 'manga:translate
        {--limit=100 : Maximum number of mangas to translate in this batch}';

    protected $description = 'Translate English synopses of mangas to French in the database';

    public function handle(TranslationService $translator): int
    {
        $limit = (int) $this->option('limit');

        $query = Manga::whereNotNull('synopsis')
            ->where('synopsis', '!=', '')
            ->orderBy('score', 'desc');

        $total = min($query->count(), $limit);

        if ($total === 0) {
            $this->info('Aucun synopsis à traduire.');

            return self::SUCCESS;
        }

        $this->info("🚀 Traduction de {$total} synopsis de mangas vers le français...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $count = 0;
        $query->chunk(25, function ($mangas) use ($translator, $bar, &$count, $limit) {
            foreach ($mangas as $manga) {
                if ($count >= $limit) {
                    return false;
                }

                $french = $translator->translateToFrench($manga->synopsis);
                if ($french && $french !== $manga->synopsis) {
                    $manga->update(['synopsis' => $french]);
                }

                $count++;
                $bar->advance();
                usleep(100_000); // 100ms pause
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ {$count} synopsis traités avec succès !");

        return self::SUCCESS;
    }
}
