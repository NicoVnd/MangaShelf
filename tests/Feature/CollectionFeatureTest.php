<?php

use App\Enums\CollectionItemStatus;
use App\Enums\CollectionMemberRole;
use App\Models\Collection;
use App\Models\CollectionMember;
use App\Models\Edition;
use App\Models\Manga;
use App\Models\User;
use App\Models\Volume;

test('authenticated user can view dashboard with collections and stats', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->assertDatabaseHas('collections', [
        'owner_id' => $user->id,
    ]);
});

test('user can create a collection without specifying privacy', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('collections.store'), [
        'name' => 'Mangathèque Salon',
    ]);

    $collection = Collection::where('name', 'Mangathèque Salon')->first();
    expect($collection)->not->toBeNull()
        ->and($collection->owner_id)->toBe($user->id);
});

test('owner can invite user and invitee receives pending invitation on dashboard', function () {
    $owner = User::factory()->create(['name' => 'Nicolas']);
    $collaborator = User::factory()->create(['name' => 'Chloé', 'email' => 'chloe@example.com']);
    $collection = Collection::factory()->create(['owner_id' => $owner->id]);

    // Send in-app invite
    $response = $this->actingAs($owner)->post(route('collections.members.store', $collection), [
        'email' => 'chloe@example.com',
        'role' => 'editor',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('collection_members', [
        'collection_id' => $collection->id,
        'user_id' => $collaborator->id,
        'status' => 'pending',
    ]);

    // Invitee sees invitation on dashboard and accepts
    $invitation = CollectionMember::where('collection_id', $collection->id)->where('user_id', $collaborator->id)->first();
    $this->actingAs($collaborator)->post(route('invitations.accept', $invitation));

    $this->assertDatabaseHas('collection_members', [
        'id' => $invitation->id,
        'status' => 'accepted',
    ]);
});

test('collaborator with accepted invitation can add manga and toggle volumes in shared collection', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $collection = Collection::factory()->create(['owner_id' => $owner->id]);

    CollectionMember::create([
        'collection_id' => $collection->id,
        'user_id' => $collaborator->id,
        'role' => CollectionMemberRole::Editor,
        'status' => 'accepted',
    ]);

    $manga = Manga::factory()->create(['title' => 'Naruto']);
    $edition = Edition::factory()->create(['manga_id' => $manga->id]);
    $volume = Volume::factory()->create(['edition_id' => $edition->id, 'number' => '1']);

    // Collaborator adds manga to collection
    $this->actingAs($collaborator)->post(route('collections.add-manga', $collection), [
        'manga_id' => $manga->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('collection_mangas', [
        'collection_id' => $collection->id,
        'manga_id' => $manga->id,
    ]);

    // Collaborator checks volume
    $this->actingAs($collaborator)->post(route('collection-items.toggle'), [
        'collection_id' => $collection->id,
        'volume_id' => $volume->id,
        'status' => 'owned',
    ])->assertRedirect();

    $this->assertDatabaseHas('collection_items', [
        'collection_id' => $collection->id,
        'volume_id' => $volume->id,
        'status' => CollectionItemStatus::Owned->value,
    ]);
});
