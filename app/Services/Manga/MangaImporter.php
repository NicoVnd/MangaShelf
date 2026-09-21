<?php

namespace App\Services\Manga;

use App\Models\Edition;
use App\Models\Manga;
use App\Models\Volume;
use App\Services\GoogleBooksService;
use App\Services\JikanService;
use App\Services\OpenLibraryService;
use App\Services\TranslationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MangaImporter
{
    public function __construct(
        protected JikanService $jikan,
        protected OpenLibraryService $openLibrary,
        protected GoogleBooksService $googleBooks,
        protected TranslationService $translator,
        protected MangaMapper $mapper
    ) {}

    /**
     * Import or update a Manga model from its MAL ID.
     */
    public function importManga(int|string $malId, bool $translate = true): ?Manga
    {
        $mangaData = $this->jikan->getMangaById($malId);

        if (! $mangaData) {
            return null;
        }

        $attributes = $this->mapper->toMangaAttributes($mangaData);

        // Translate synopsis if needed and not already French
        if ($translate && ! empty($attributes['synopsis'])) {
            try {
                $attributes['synopsis'] = $this->translator->translateToFrench($attributes['synopsis']);
            } catch (\Throwable $e) {
                Log::warning("Translation failed for manga {$malId}: ".$e->getMessage());
            }
        }

        return Manga::updateOrCreate(
            ['external_id' => (string) $malId],
            $attributes
        );
    }

    /**
     * Import or update all editions and volumes for a Manga in the database.
     *
     * @return array<int, Volume>
     */
    public function importVolumes(Manga $manga): array
    {
        $mangaData = [
            'title' => $manga->title,
            'status' => $manga->status,
            'total_volumes' => $manga->total_volumes,
            'cover_url' => $manga->cover_url,
        ];

        // Fetch French volumes combining Open Library and Google Books
        $rawVolumes = $this->openLibrary->getVolumesForManga($mangaData, $this->googleBooks);

        if (empty($rawVolumes)) {
            return [];
        }

        return DB::transaction(function () use ($manga, $rawVolumes) {
            // Find or create the primary French edition
            $primaryPublisher = null;
            foreach ($rawVolumes as $vol) {
                if (! empty($vol['publisher']) && $vol['publisher'] !== 'Édition Française') {
                    $primaryPublisher = $vol['publisher'];
                    break;
                }
            }

            $edition = Edition::firstOrCreate(
                [
                    'manga_id' => $manga->id,
                    'language' => 'fr',
                ],
                [
                    'publisher' => $primaryPublisher ?? 'Édition Standard',
                    'collection_name' => 'Édition Originale',
                    'format' => 'Poche / Tankōbon',
                ]
            );

            if ($primaryPublisher && $edition->publisher === 'Édition Standard') {
                $edition->update(['publisher' => $primaryPublisher]);
            }

            $savedVolumes = [];

            foreach ($rawVolumes as $rawVol) {
                $volAttributes = $this->mapper->toVolumeAttributes($rawVol, $edition->id);

                $volume = Volume::updateOrCreate(
                    [
                        'edition_id' => $edition->id,
                        'number' => $volAttributes['number'],
                    ],
                    $volAttributes
                );

                $savedVolumes[] = $volume;
            }

            return $savedVolumes;
        });
    }

    /**
     * Import a full manga bundle (manga + edition + volumes) in one step.
     */
    public function importFull(int|string $malId): ?Manga
    {
        $manga = $this->importManga($malId);

        if (! $manga) {
            return null;
        }

        $this->importVolumes($manga);

        return $manga->fresh(['editions.volumes']);
    }
}
