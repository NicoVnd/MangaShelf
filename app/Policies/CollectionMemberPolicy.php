<?php

namespace App\Policies;

use App\Models\CollectionMember;
use App\Models\User;

class CollectionMemberPolicy
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
     * Determine whether the user can view the member.
     */
    public function view(User $user, CollectionMember $collectionMember): bool
    {
        return $user->can('view', $collectionMember->collection);
    }

    /**
     * Determine whether the user can create memberships.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the membership.
     */
    public function update(User $user, CollectionMember $collectionMember): bool
    {
        return $user->can('manageMembers', $collectionMember->collection);
    }

    /**
     * Determine whether the user can delete the membership.
     */
    public function delete(User $user, CollectionMember $collectionMember): bool
    {
        if ($collectionMember->user_id === $user->id) {
            return true; // Quitter soi-même une collection
        }

        return $user->can('manageMembers', $collectionMember->collection);
    }
}
