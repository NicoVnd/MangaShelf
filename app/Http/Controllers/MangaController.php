<?php

namespace App\Http\Controllers;

use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Manga;
use App\Services\GoogleBooksService;
use App\Services\JikanService;
use App\Services\Manga\MangaImporter;
use App\Services\OpenLibraryService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MangaController extends Controller
{
    /**
     * Display a detailed manga page with Jikan info. Volumes are loaded asynchronously via deferred props.
     * Automatically imports the manga and its volumes in database and provides user's collection status.
     */
    public function show(
        int|string $id,
        Request $request,
        JikanService $jikan,
        OpenLibraryService $openLibrary,
        GoogleBooksService $googleBooks,
        MangaImporter $importer,
        TranslationService $translator
    ): Response {
        $user = $request->user();

        // 1. Try to find in local DB first
        $mangaModel = Manga::with(['editions.volumes'])->where('external_id', (string) $id)->first();

        if (! $mangaModel) {
            $mangaModel = $importer->importManga($id);
        }

        // 2. Ensure synopsis is translated to French if needed
        $synopsis = $mangaModel?->synopsis;
        if ($synopsis) {
            // If English or untranslated, translate on-the-fly and save back to DB
            $frenchSynopsis = $translator->translateToFrench($synopsis);
            if ($frenchSynopsis && $frenchSynopsis !== $synopsis) {
                $synopsis = $frenchSynopsis;
                if ($mangaModel) {
                    $mangaModel->update(['synopsis' => $frenchSynopsis]);
                }
            }
        }

        // 3. Format details payload for Inertia
        $mangaData = $mangaModel ? [
            'external_id' => $mangaModel->external_id,
            'title' => $mangaModel->title,
            'original_title' => $mangaModel->original_title,
            'synopsis' => $synopsis,
            'author' => $mangaModel->author,
            'genres' => $mangaModel->genres ?? [],
            'status' => $mangaModel->status,
            'total_volumes' => $mangaModel->total_volumes,
            'score' => $mangaModel->score,
            'cover_url' => $mangaModel->cover_url,
        ] : $jikan->getMangaById($id);

        if (! $mangaData) {
            abort(404, 'Manga introuvable.');
        }

        // 3. User collections list for quick-add modal
        $userCollections = [];
        $collectedVolumeStatus = [];

        if ($user) {
            $personal = Collection::firstOrCreate(
                ['owner_id' => $user->id, 'type' => CollectionType::Personal],
                ['name' => 'Ma Collection Principale']
            );

            $ownedColls = $user->ownedCollections()->get(['id', 'name', 'type']);
            $sharedColls = $user->accessibleCollections()->get(['collections.id', 'name', 'type']);
            $userCollections = $ownedColls->merge($sharedColls)->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type->value ?? 'personal',
            ]);

            // If manga is in DB, fetch statuses for user in their collections
            if ($mangaModel) {
                $volumeIds = $mangaModel->volumes()->pluck('volumes.id');
                $items = CollectionItem::whereIn('volume_id', $volumeIds)
                    ->whereIn('collection_id', $userCollections->pluck('id'))
                    ->get(['volume_id', 'collection_id', 'status']);

                foreach ($items as $item) {
                    $collectedVolumeStatus[$item->volume_id] = [
                        'collection_id' => $item->collection_id,
                        'status' => $item->status->value,
                    ];
                }
            }
        }

        return Inertia::render('Manga/Show', [
            'manga' => $mangaData,
            'userCollections' => $userCollections,
            'defaultCollectionId' => $userCollections[0]['id'] ?? null,
            // Defer volumes: if in DB, load directly from DB; otherwise fetch from APIs & persist
            'volumes' => Inertia::defer(function () use ($mangaModel, $mangaData, $importer, $openLibrary, $googleBooks, $collectedVolumeStatus) {
                // If we have saved volumes in DB, return them
                if ($mangaModel && $mangaModel->volumes()->exists()) {
                    return $mangaModel->volumes()->orderByRaw('CAST(number AS UNSIGNED) ASC')->get()->map(fn ($v) => [
                        'id' => $v->id,
                        'number' => $v->number,
                        'title' => $v->title ?? "{$mangaModel->title} - Tome {$v->number}",
                        'publisher' => $v->edition->publisher ?? 'Édition Standard',
                        'publication_date' => $v->publication_date?->format('Y-m-d'),
                        'isbn13' => $v->isbn13,
                        'isbn10' => $v->isbn10,
                        'cover_url' => $v->cover_url ?? $mangaModel->cover_url,
                        'status' => ($v->publication_date && $v->publication_date->isFuture()) ? 'upcoming' : 'released',
                        'collection_status' => $collectedVolumeStatus[$v->id]['status'] ?? null,
                    ])->toArray();
                }

                // If manga exists in DB but volumes are not yet imported, import and save them
                if ($mangaModel) {
                    $saved = $importer->importVolumes($mangaModel);
                    if (! empty($saved)) {
                        return collect($saved)->map(fn ($v) => [
                            'id' => $v->id,
                            'number' => $v->number,
                            'title' => $v->title ?? "{$mangaModel->title} - Tome {$v->number}",
                            'publisher' => $v->edition->publisher ?? 'Édition Standard',
                            'publication_date' => $v->publication_date?->format('Y-m-d'),
                            'isbn13' => $v->isbn13,
                            'isbn10' => $v->isbn10,
                            'cover_url' => $v->cover_url ?? $mangaModel->cover_url,
                            'status' => ($v->publication_date && $v->publication_date->isFuture()) ? 'upcoming' : 'released',
                            'collection_status' => $collectedVolumeStatus[$v->id]['status'] ?? null,
                        ])->toArray();
                    }
                }

                return $openLibrary->getVolumesForManga($mangaData, $googleBooks);
            }),
        ]);
    }
}
