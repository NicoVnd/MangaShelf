<?php

namespace App\Http\Controllers;

use App\Models\Volume;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PlanningController extends Controller
{
    /**
     * Display the release planning page with upcoming volumes grouped by week.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all'); // 'all' or 'mine'

        $startOfWeek = Carbon::now()->startOfWeek();
        $endRange = Carbon::now()->addMonths(3);

        $query = Volume::query()
            ->with(['edition.manga'])
            ->whereNotNull('publication_date')
            ->where('publication_date', '>=', $startOfWeek)
            ->where('publication_date', '<=', $endRange)
            ->orderBy('publication_date');

        // If filtering by user's followed series
        if ($filter === 'mine' && $user) {
            $followedMangaIds = $user->ownedCollections()
                ->with('mangas')
                ->get()
                ->flatMap(fn ($col) => $col->mangas->pluck('id'))
                ->unique();

            $query->whereHas('edition', function ($q) use ($followedMangaIds) {
                $q->whereIn('manga_id', $followedMangaIds);
            });
        }

        $volumes = $query->get();

        // Group by week
        $weeklyGroups = $volumes
            ->groupBy(fn ($vol) => Carbon::parse($vol->publication_date)->startOfWeek()->format('Y-m-d'))
            ->map(function ($weekVolumes, $weekStart) {
                return [
                    'week_start' => $weekStart,
                    'week_label' => Carbon::parse($weekStart)->translatedFormat('d M').' — '.Carbon::parse($weekStart)->addDays(6)->translatedFormat('d M Y'),
                    'volumes' => $weekVolumes->map(fn ($vol) => [
                        'id' => $vol->id,
                        'number' => $vol->number,
                        'title' => $vol->title ?? ($vol->edition?->manga?->title.' - Tome '.$vol->number),
                        'publication_date' => $vol->publication_date?->format('d/m/Y'),
                        'cover_url' => $vol->cover_url ?? $vol->edition?->manga?->cover_url,
                        'isbn13' => $vol->isbn13,
                        'manga' => [
                            'id' => $vol->edition?->manga?->id,
                            'external_id' => $vol->edition?->manga?->external_id,
                            'title' => $vol->edition?->manga?->title,
                        ],
                        'publisher' => $vol->edition?->publisher,
                    ])->values(),
                ];
            })
            ->values();

        return Inertia::render('Planning', [
            'weeklyGroups' => $weeklyGroups,
            'filter' => $filter,
            'isAuthenticated' => (bool) $user,
        ]);
    }
}
