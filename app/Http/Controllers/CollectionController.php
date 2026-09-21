<?php

namespace App\Http\Controllers;

use App\Enums\CollectionItemStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Manga;
use App\Services\Manga\MangaImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    /**
     * Display a listing of personal and shared collections with pending invitations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure user has at least one default collection
        Collection::firstOrCreate(
            [
                'owner_id' => $user->id,
            ],
            [
                'name' => 'Ma Mangathèque',
                'type' => CollectionType::Personal,
            ]
        );

        $ownedCollections = $user->ownedCollections()
            ->withCount(['items', 'members' => fn ($q) => $q->where('status', 'accepted')])
            ->get();

        $sharedCollections = $user->accessibleCollections()
            ->withCount(['items', 'members' => fn ($q) => $q->where('status', 'accepted')])
            ->with(['owner'])
            ->get();

        // Pending invitations for this user
        $pendingInvitations = $user->pendingInvitations()
            ->with(['collection.owner'])
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'role' => $inv->role->value,
                'collection' => [
                    'id' => $inv->collection->id,
                    'name' => $inv->collection->name,
                    'owner_name' => $inv->collection->owner->name,
                ],
            ]);

        // Global stats across all accessible collections
        $allAccessibleIds = $ownedCollections->pluck('id')->merge($sharedCollections->pluck('id'))->unique();
        $totalItems = CollectionItem::whereIn('collection_id', $allAccessibleIds)->count();
        $totalOwned = CollectionItem::whereIn('collection_id', $allAccessibleIds)
            ->where('status', CollectionItemStatus::Owned)
            ->count();
        $totalRead = CollectionItem::whereIn('collection_id', $allAccessibleIds)
            ->where('status', CollectionItemStatus::Read)
            ->count();
        $totalWishlist = CollectionItem::whereIn('collection_id', $allAccessibleIds)
            ->where('status', CollectionItemStatus::Wishlist)
            ->count();

        // Get series and their progression from user's primary/owned collections
        $primaryCollection = $ownedCollections->first();
        $userSeries = [];

        if ($primaryCollection) {
            $mangaIdsFromItems = CollectionItem::where('collection_id', $primaryCollection->id)
                ->with(['volume.edition'])
                ->get()
                ->pluck('volume.edition.manga_id')
                ->filter()
                ->unique();

            $mangaIdsFromCollection = $primaryCollection->mangas()->pluck('mangas.id');
            $allSeriesIds = $mangaIdsFromItems->merge($mangaIdsFromCollection)->unique();

            $userSeries = Manga::whereIn('id', $allSeriesIds)
                ->with(['volumes'])
                ->get()
                ->map(function ($manga) use ($primaryCollection) {
                    $ownedCount = CollectionItem::where('collection_id', $primaryCollection->id)
                        ->whereHas('volume.edition', fn ($q) => $q->where('manga_id', $manga->id))
                        ->where('status', CollectionItemStatus::Owned)
                        ->count();

                    $totalVolumes = $manga->volumes->count() ?: ($manga->total_volumes ?: 0);
                    $percentage = $totalVolumes > 0 ? min(100, (int) round(($ownedCount / $totalVolumes) * 100)) : 0;

                    return [
                        'id' => $manga->id,
                        'external_id' => $manga->external_id,
                        'title' => $manga->title,
                        'author' => $manga->author,
                        'cover_url' => $manga->cover_url,
                        'status' => $manga->status,
                        'owned_count' => $ownedCount,
                        'total_volumes' => $totalVolumes,
                        'percentage' => $percentage,
                        'collection_id' => $primaryCollection->id,
                    ];
                })
                ->values();
        }

        return Inertia::render('Dashboard', [
            'ownedCollections' => $ownedCollections,
            'sharedCollections' => $sharedCollections,
            'pendingInvitations' => $pendingInvitations,
            'userSeries' => $userSeries,
            'stats' => [
                'total_collections' => $ownedCollections->count() + $sharedCollections->count(),
                'total_items' => $totalItems,
                'total_owned' => $totalOwned,
                'total_read' => $totalRead,
                'total_wishlist' => $totalWishlist,
            ],
        ]);
    }

    /**
     * Store a newly created collection.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Collection::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $collection = $request->user()->ownedCollections()->create([
            'name' => $validated['name'],
            'type' => CollectionType::Personal,
        ]);

        return redirect()->route('collections.show', $collection)->with('success', 'Collection créée avec succès !');
    }

    /**
     * Display the specified collection with detailed user statistics.
     */
    public function show(Collection $collection, Request $request): Response
    {
        Gate::authorize('view', $collection);

        $collection->load([
            'owner',
            'members' => fn ($q) => $q->with('user'),
            'items.volume.edition.manga',
            'items.user',
        ]);

        // Build list of all active contributors (owner + accepted members)
        $contributors = collect([
            [
                'id' => $collection->owner->id,
                'name' => $collection->owner->name,
                'email' => $collection->owner->email,
                'avatar' => $collection->owner->avatar,
                'is_owner' => true,
            ],
        ]);

        foreach ($collection->members->where('status', 'accepted') as $member) {
            if ($member->user && $member->user->id !== $collection->owner->id) {
                $contributors->push([
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'avatar' => $member->user->avatar,
                    'is_owner' => false,
                ]);
            }
        }

        // Group items by manga and get all available volumes of that manga
        $mangasGrouped = [];

        $mangaIdsFromItems = $collection->items->pluck('volume.edition.manga_id')->filter()->unique();
        $mangaIdsFromCollection = $collection->mangas()->pluck('mangas.id');
        $allMangaIds = $mangaIdsFromItems->merge($mangaIdsFromCollection)->unique();

        $collectionMangas = Manga::whereIn('id', $allMangaIds)
            ->with(['volumes' => fn ($q) => $q->orderByRaw('CAST(number AS UNSIGNED) ASC')])
            ->get();

        // Breakdown per user statistics
        $userBreakdown = [];
        foreach ($contributors as $contrib) {
            $userBreakdown[$contrib['id']] = [
                'user_id' => $contrib['id'],
                'user_name' => $contrib['name'],
                'is_owner' => $contrib['is_owner'],
                'total_owned' => 0,
                'mangas_count' => 0,
                'mangas' => [], // Manga breakdown per person
            ];
        }

        foreach ($collectionMangas as $manga) {
            $mangaItems = $collection->items->filter(fn ($it) => $it->volume && $it->volume->edition && $it->volume->edition->manga_id === $manga->id);
            $itemsByVolumeId = $mangaItems->keyBy('volume_id');

            // Count ownership per user for this manga
            $mangaOwnersBreakdown = [];

            // Format all volumes of this manga with possession status & user assignment
            $allVolumesFormatted = $manga->volumes->map(function ($vol) use ($itemsByVolumeId, &$mangaOwnersBreakdown, &$userBreakdown, $manga) {
                $item = $itemsByVolumeId[$vol->id] ?? null;
                $isOwned = $item && $item->status === CollectionItemStatus::Owned;
                $assignedUser = $item?->user;

                if ($isOwned && $assignedUser) {
                    $userId = $assignedUser->id;
                    $mangaOwnersBreakdown[$userId] = ($mangaOwnersBreakdown[$userId] ?? 0) + 1;

                    if (isset($userBreakdown[$userId])) {
                        $userBreakdown[$userId]['total_owned']++;
                        if (! isset($userBreakdown[$userId]['mangas'][$manga->id])) {
                            $userBreakdown[$userId]['mangas'][$manga->id] = [
                                'title' => $manga->title,
                                'count' => 0,
                            ];
                            $userBreakdown[$userId]['mangas_count']++;
                        }
                        $userBreakdown[$userId]['mangas'][$manga->id]['count']++;
                    }
                }

                return [
                    'id' => $vol->id,
                    'number' => $vol->number,
                    'title' => $vol->title ?? "{$manga->title} - Tome {$vol->number}",
                    'isbn13' => $vol->isbn13,
                    'cover_url' => $vol->cover_url ?? $manga->cover_url,
                    'is_owned' => $isOwned,
                    'item_id' => $item?->id,
                    'item_status' => $item?->status->value,
                    'user' => $assignedUser ? [
                        'id' => $assignedUser->id,
                        'name' => $assignedUser->name,
                    ] : null,
                ];
            });

            // Format owner summary for this manga (e.g. "Nicolas (25 tomes), Chloé (10 tomes)")
            $ownersSummary = [];
            foreach ($mangaOwnersBreakdown as $uId => $cnt) {
                $matchedUser = $contributors->firstWhere('id', $uId);
                if ($matchedUser) {
                    $ownersSummary[] = [
                        'user_id' => $uId,
                        'name' => $matchedUser['name'],
                        'count' => $cnt,
                    ];
                }
            }

            $mangasGrouped[] = [
                'manga' => [
                    'id' => $manga->id,
                    'external_id' => $manga->external_id,
                    'title' => $manga->title,
                    'cover_url' => $manga->cover_url,
                    'status' => $manga->status,
                    'total_volumes' => $manga->total_volumes,
                ],
                'volumes' => $allVolumesFormatted,
                'owned_count' => $allVolumesFormatted->where('is_owned', true)->count(),
                'total_count' => $allVolumesFormatted->count(),
                'owners_summary' => $ownersSummary,
            ];
        }

        // Available mangas in DB that can be added to this collection
        $availableMangas = Manga::orderBy('title')->get(['id', 'title', 'cover_url', 'external_id', 'total_volumes']);

        return Inertia::render('Collections/Show', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'type' => $collection->type->value,
                'is_owner' => $collection->owner_id === $request->user()->id || $request->user()->isAdmin(),
                'owner' => [
                    'id' => $collection->owner->id,
                    'name' => $collection->owner->name,
                    'email' => $collection->owner->email,
                    'avatar' => $collection->owner->avatar,
                ],
                'members' => $collection->members->map(fn ($m) => [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $m->user->name,
                    'email' => $m->user->email,
                    'avatar' => $m->user->avatar,
                    'role' => $m->role->value,
                    'status' => $m->status,
                ]),
            ],
            'contributors' => $contributors->values(),
            'userBreakdown' => array_values($userBreakdown),
            'mangasGrouped' => $mangasGrouped,
            'availableMangas' => $availableMangas,
            'totalItemsCount' => $collection->items->where('status', CollectionItemStatus::Owned)->count(),
        ]);
    }

    /**
     * Add a manga to a collection.
     */
    public function addManga(Request $request, Collection $collection, MangaImporter $importer): RedirectResponse
    {
        Gate::authorize('update', $collection);

        $validated = $request->validate([
            'manga_id' => ['required', 'exists:mangas,id'],
        ]);

        $manga = Manga::findOrFail($validated['manga_id']);

        if (! $manga->volumes()->exists()) {
            $importer->importVolumes($manga);
        }

        $collection->mangas()->syncWithoutDetaching([$manga->id]);

        return back()->with('success', "Manga '{$manga->title}' ajouté à la collection ! Cochez vos tomes ci-dessous.");
    }

    /**
     * Update the specified collection settings.
     */
    public function update(Request $request, Collection $collection): RedirectResponse
    {
        Gate::authorize('update', $collection);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $collection->update($validated);

        return back()->with('success', 'Paramètres de la collection mis à jour.');
    }

    /**
     * Remove the specified collection.
     */
    public function destroy(Collection $collection): RedirectResponse
    {
        Gate::authorize('delete', $collection);

        $collection->delete();

        return redirect()->route('dashboard')->with('success', 'Collection supprimée.');
    }
}
