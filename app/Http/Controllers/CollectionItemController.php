<?php

namespace App\Http\Controllers;

use App\Enums\CollectionItemStatus;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Volume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CollectionItemController extends Controller
{
    /**
     * Store or toggle a volume in a collection.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'collection_id' => ['required', 'exists:collections,id'],
            'volume_id' => ['required', 'exists:volumes,id'],
            'status' => ['required', 'string', 'in:owned,wishlist,reading,read,lent,sold'],
            'user_id' => ['nullable', 'exists:users,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $collection = Collection::findOrFail($validated['collection_id']);
        Gate::authorize('update', $collection);

        $status = CollectionItemStatus::from($validated['status']);
        $assignedUserId = $validated['user_id'] ?? $request->user()->id;

        $item = CollectionItem::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'volume_id' => $validated['volume_id'],
            ],
            [
                'user_id' => $assignedUserId,
                'status' => $status,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'purchased_at' => $status === CollectionItemStatus::Owned ? now() : null,
                'read_at' => $status === CollectionItemStatus::Read ? now() : null,
            ]
        );

        return back()->with('success', "Tome mis à jour dans la collection ({$status->value}).");
    }

    /**
     * Toggle or quick add/remove a volume by volume_id in user's default/selected collection.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'collection_id' => ['required', 'exists:collections,id'],
            'volume_id' => ['required', 'exists:volumes,id'],
            'status' => ['nullable', 'string', 'in:owned,wishlist,reading,read,lent,sold'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $collection = Collection::findOrFail($validated['collection_id']);
        Gate::authorize('update', $collection);

        $existing = CollectionItem::where('collection_id', $collection->id)
            ->where('volume_id', $validated['volume_id'])
            ->first();

        $targetStatus = isset($validated['status'])
            ? CollectionItemStatus::from($validated['status'])
            : CollectionItemStatus::Owned;

        $targetUserId = $validated['user_id'] ?? $request->user()->id;

        if ($existing) {
            if ($existing->status === $targetStatus && (! isset($validated['user_id']) || $existing->user_id === (int) $validated['user_id'])) {
                // If clicking same status and same user, toggle remove
                $existing->delete();

                return back()->with('success', 'Tome retiré de la collection.');
            } else {
                // Switch status or reassign owner
                $existing->update([
                    'status' => $targetStatus,
                    'user_id' => $targetUserId,
                    'read_at' => $targetStatus === CollectionItemStatus::Read ? now() : $existing->read_at,
                ]);

                return back()->with('success', "Statut mis à jour ({$targetStatus->value}).");
            }
        }

        CollectionItem::create([
            'collection_id' => $collection->id,
            'volume_id' => $validated['volume_id'],
            'user_id' => $targetUserId,
            'status' => $targetStatus,
            'purchased_at' => $targetStatus === CollectionItemStatus::Owned ? now() : null,
        ]);

        return back()->with('success', "Tome ajouté ({$targetStatus->value}) !");
    }

    /**
     * Update the specified collection item.
     */
    public function update(Request $request, CollectionItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:owned,wishlist,reading,read,lent,sold'],
            'user_id' => ['nullable', 'exists:users,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $status = CollectionItemStatus::from($validated['status']);

        $item->update([
            'status' => $status,
            'user_id' => $validated['user_id'] ?? $item->user_id,
            'purchase_price' => $validated['purchase_price'] ?? $item->purchase_price,
            'notes' => $validated['notes'] ?? $item->notes,
            'read_at' => $status === CollectionItemStatus::Read ? ($item->read_at ?? now()) : $item->read_at,
        ]);

        return back()->with('success', 'Tome mis à jour.');
    }

    /**
     * Remove the specified item from collection.
     */
    public function destroy(CollectionItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return back()->with('success', 'Tome retiré de la collection.');
    }
}
