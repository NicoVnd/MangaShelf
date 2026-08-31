<?php

use App\Enums\CollectionMemberRole;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\CollectionMember;
use App\Models\Edition;
use App\Models\Manga;
use App\Models\User;

test('admin can perform any action on collections, editions and mangas', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $collection = Collection::factory()->create(['owner_id' => $owner->id]);
    $item = CollectionItem::factory()->create(['collection_id' => $collection->id]);
    $manga = Manga::factory()->create();
    $edition = Edition::factory()->create(['manga_id' => $manga->id]);

    expect($admin->can('view', $collection))->toBeTrue()
        ->and($admin->can('update', $collection))->toBeTrue()
        ->and($admin->can('delete', $collection))->toBeTrue()
        ->and($admin->can('manageMembers', $collection))->toBeTrue()
        ->and($admin->can('update', $item))->toBeTrue()
        ->and($admin->can('delete', $item))->toBeTrue()
        ->and($admin->can('create', Manga::class))->toBeTrue()
        ->and($admin->can('update', $manga))->toBeTrue()
        ->and($admin->can('delete', $manga))->toBeTrue()
        ->and($admin->can('create', Edition::class))->toBeTrue();
});

test('regular users can only manage their own collections or shared editor roles', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $viewer = User::factory()->create();
    $stranger = User::factory()->create();

    $collection = Collection::factory()->shared()->create(['owner_id' => $owner->id]);

    CollectionMember::factory()->editor()->create([
        'collection_id' => $collection->id,
        'user_id' => $editor->id,
    ]);

    CollectionMember::factory()->create([
        'collection_id' => $collection->id,
        'user_id' => $viewer->id,
        'role' => CollectionMemberRole::Viewer,
    ]);

    // Owner checks
    expect($owner->can('update', $collection))->toBeTrue()
        ->and($owner->can('delete', $collection))->toBeTrue()
        ->and($owner->can('manageMembers', $collection))->toBeTrue();

    // Editor checks
    expect($editor->can('view', $collection))->toBeTrue()
        ->and($editor->can('update', $collection))->toBeTrue()
        ->and($editor->can('delete', $collection))->toBeFalse()
        ->and($editor->can('manageMembers', $collection))->toBeFalse();

    // Viewer checks
    expect($viewer->can('view', $collection))->toBeTrue()
        ->and($viewer->can('update', $collection))->toBeFalse()
        ->and($viewer->can('delete', $collection))->toBeFalse();

    // Stranger checks
    expect($stranger->can('view', $collection))->toBeFalse()
        ->and($stranger->can('update', $collection))->toBeFalse();
});
