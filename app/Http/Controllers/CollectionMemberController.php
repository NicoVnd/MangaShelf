<?php

namespace App\Http\Controllers;

use App\Enums\CollectionMemberRole;
use App\Models\Collection;
use App\Models\CollectionMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CollectionMemberController extends Controller
{
    /**
     * Invite a user to join a shared collection (creates a pending invitation).
     */
    public function store(Request $request, Collection $collection): RedirectResponse
    {
        Gate::authorize('manageMembers', $collection);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', 'in:editor,viewer'],
        ]);

        $userToAdd = User::where('email', $validated['email'])->firstOrFail();

        // Cannot invite owner
        if ($userToAdd->id === $collection->owner_id) {
            return back()->withErrors(['email' => 'Cet utilisateur est le créateur de la collection.']);
        }

        // Check if already invited or member
        $existing = $collection->members()->where('user_id', $userToAdd->id)->first();
        if ($existing) {
            if ($existing->status === 'accepted') {
                return back()->withErrors(['email' => 'Cet utilisateur fait déjà partie de la collection.']);
            }
            $existing->update([
                'role' => CollectionMemberRole::from($validated['role']),
                'status' => 'pending',
            ]);

            return back()->with('success', "Demande renvoyée à {$userToAdd->name}.");
        }

        $collection->members()->create([
            'user_id' => $userToAdd->id,
            'role' => CollectionMemberRole::from($validated['role']),
            'status' => 'pending',
        ]);

        return back()->with('success', "Invitation envoyée à {$userToAdd->name} ! Il la verra sur son Dashboard.");
    }

    /**
     * Accept a pending invitation.
     */
    public function accept(CollectionMember $invitation, Request $request): RedirectResponse
    {
        if ($invitation->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $invitation->update(['status' => 'accepted']);

        return back()->with('success', "Vous avez rejoint la collection '{$invitation->collection->name}' !");
    }

    /**
     * Decline / reject a pending invitation.
     */
    public function decline(CollectionMember $invitation, Request $request): RedirectResponse
    {
        if ($invitation->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation refusée.');
    }

    /**
     * Update member role.
     */
    public function update(Request $request, Collection $collection, CollectionMember $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $collection);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:owner,editor,viewer'],
        ]);

        $member->update([
            'role' => CollectionMemberRole::from($validated['role']),
        ]);

        return back()->with('success', 'Rôle mis à jour.');
    }

    /**
     * Remove a member from the collection.
     */
    public function destroy(Collection $collection, CollectionMember $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $collection);

        $member->delete();

        return back()->with('success', 'Membre retiré de la collection.');
    }
}
