<?php

namespace App\Http\Controllers;

use App\Models\Manga;
use App\Models\Volume;
use App\Services\JikanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home/discover page with latest releases, upcoming releases, and popular mangas.
     */
    public function __invoke(Request $request, JikanService $jikan): Response
    {
        $search = $request->input('search');

        if ($search) {
            // 1. Search local DB first
            $dbMangas = Manga::where('title', 'like', "%{$search}%")
                ->orWhere('original_title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orderByRaw('score DESC NULLS LAST')
                ->limit(24)
                ->get();

            if ($dbMangas->isNotEmpty()) {
                $mangas = $dbMangas->map(fn (Manga $m) => [
                    'external_id' => $m->external_id,
                    'title' => $m->title,
                    'original_title' => $m->original_title,
                    'synopsis' => $m->synopsis,
                    'author' => $m->author,
                    'genres' => $m->genres ?? [],
                    'status' => $m->status,
                    'total_volumes' => $m->total_volumes,
                    'score' => $m->score,
                    'cover_url' => $m->cover_url,
                ])->all();
            } else {
                $mangas = $jikan->searchManga($search, limit: 12);
            }
        } else {
            // Fetch top mangas from local DB
            $dbMangas = Manga::orderByRaw('score DESC NULLS LAST')
                ->limit(24)
                ->get();

            if ($dbMangas->isNotEmpty()) {
                $mangas = $dbMangas->map(fn (Manga $m) => [
                    'external_id' => $m->external_id,
                    'title' => $m->title,
                    'original_title' => $m->original_title,
                    'synopsis' => $m->synopsis,
                    'author' => $m->author,
                    'genres' => $m->genres ?? [],
                    'status' => $m->status,
                    'total_volumes' => $m->total_volumes,
                    'score' => $m->score,
                    'cover_url' => $m->cover_url,
                ])->all();
            } else {
                $mangas = $jikan->getPopularMangas();
            }
        }

        // Fetch recent/upcoming volumes for the "Nouveautés" section
        $recentReleases = Volume::query()
            ->with(['edition.manga'])
            ->whereNotNull('publication_date')
            ->where('publication_date', '<=', Carbon::now())
            ->orderByDesc('publication_date')
            ->limit(12)
            ->get()
            ->map(fn ($vol) => [
                'id' => $vol->id,
                'number' => $vol->number,
                'title' => $vol->title ?? ($vol->edition?->manga?->title.' - Tome '.$vol->number),
                'publication_date' => $vol->publication_date?->format('d/m/Y'),
                'cover_url' => $vol->cover_url ?? $vol->edition?->manga?->cover_url,
                'manga' => [
                    'external_id' => $vol->edition?->manga?->external_id,
                    'title' => $vol->edition?->manga?->title,
                ],
            ]);

        $upcomingReleases = Volume::query()
            ->with(['edition.manga'])
            ->whereNotNull('publication_date')
            ->where('publication_date', '>', Carbon::now())
            ->orderBy('publication_date')
            ->limit(12)
            ->get()
            ->map(fn ($vol) => [
                'id' => $vol->id,
                'number' => $vol->number,
                'title' => $vol->title ?? ($vol->edition?->manga?->title.' - Tome '.$vol->number),
                'publication_date' => $vol->publication_date?->format('d/m/Y'),
                'cover_url' => $vol->cover_url ?? $vol->edition?->manga?->cover_url,
                'manga' => [
                    'external_id' => $vol->edition?->manga?->external_id,
                    'title' => $vol->edition?->manga?->title,
                ],
            ]);

        return Inertia::render('Welcome', [
            'mangas' => $mangas,
            'recentReleases' => $recentReleases,
            'upcomingReleases' => $upcomingReleases,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
