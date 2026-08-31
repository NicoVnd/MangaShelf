<?php

namespace App\Policies;

use App\Enums\CollectionMemberRole;
use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
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
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Collection $collection): bool
    {
        if ($collection->owner_id === $user->id) {
            return true;
        }

        return $collection->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Collection $collection): bool
    {
        if ($collection->owner_id === $user->id) {
            return true;
        }

        return $collection->members()
            ->where('user_id', $user->id)
            ->whereIn('role', [CollectionMemberRole::Owner, CollectionMemberRole::Editor])
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Collection $collection): bool
    {
        return $collection->owner_id === $user->id;
    }

    /**
     * Determine whether the user can manage members of the collection.
     */
    public function manageMembers(User $user, Collection $collection): bool
    {
        if ($collection->owner_id === $user->id) {
            return true;
        }

        return $collection->members()
            ->where('user_id', $user->id)
            ->where('role', CollectionMemberRole::Owner)
            ->exists();
    }
}
