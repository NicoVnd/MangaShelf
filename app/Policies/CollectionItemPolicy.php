<?php

namespace App\Policies;

use App\Enums\CollectionMemberRole;
use App\Models\CollectionItem;
use App\Models\User;

class CollectionItemPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the item.
     */
    public function view(User $user, CollectionItem $item): bool
    {
        $collection = $item->collection;

        if ($collection->owner_id === $user->id) {
            return true;
        }

        return $collection->members()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Determine whether the user can create items in a collection.
     */
    public function create(User $user, ?CollectionItem $item = null): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the item.
     */
    public function update(User $user, CollectionItem $item): bool
    {
        $collection = $item->collection;

        if ($collection->owner_id === $user->id) {
            return true;
        }

        if ($item->user_id === $user->id) {
            return true;
        }

        return $collection->members()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereIn('role', [CollectionMemberRole::Owner, CollectionMemberRole::Editor])
            ->exists();
    }

    /**
     * Determine whether the user can delete the item.
     */
    public function delete(User $user, CollectionItem $item): bool
    {
        $collection = $item->collection;

        if ($collection->owner_id === $user->id) {
            return true;
        }

        if ($item->user_id === $user->id) {
            return true;
        }

        return $collection->members()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereIn('role', [CollectionMemberRole::Owner, CollectionMemberRole::Editor])
            ->exists();
    }
}
