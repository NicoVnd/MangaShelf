<?php

use App\Enums\CollectionItemStatus;
use App\Enums\CollectionMemberRole;
use App\Enums\CollectionType;
use App\Enums\UserRole;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\CollectionMember;
use App\Models\Edition;
use App\Models\Manga;
use App\Models\User;
use App\Models\Volume;

test('user has role and admin check', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->role)->toBe(UserRole::User);
});

test('manga relationships work correctly', function () {
    $manga = Manga::factory()->create();
    $edition = Edition::factory()->create(['manga_id' => $manga->id]);
    $volume = Volume::factory()->create(['edition_id' => $edition->id]);

    expect($manga->editions)->toHaveCount(1)
        ->and($manga->editions->first()->id)->toBe($edition->id)
        ->and($manga->volumes)->toHaveCount(1)
        ->and($manga->volumes->first()->id)->toBe($volume->id)
        ->and($edition->manga->id)->toBe($manga->id)
        ->and($volume->edition->id)->toBe($edition->id);
});

test('collection and memberships work correctly', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $collection = Collection::factory()->shared()->create(['owner_id' => $owner->id]);

    $membership = CollectionMember::factory()->editor()->create([
        'collection_id' => $collection->id,
        'user_id' => $member->id,
    ]);

    expect($collection->owner->id)->toBe($owner->id)
        ->and($collection->type)->toBe(CollectionType::Shared)
        ->and($collection->members)->toHaveCount(1)
        ->and($collection->users->first()->id)->toBe($member->id)
        ->and($membership->role)->toBe(CollectionMemberRole::Editor)
        ->and($owner->ownedCollections)->toHaveCount(1)
        ->and($member->collectionMemberships)->toHaveCount(1)
        ->and($member->accessibleCollections)->toHaveCount(1);
});

test('collection items can be added and cast properly', function () {
    $collection = Collection::factory()->create();
    $volume = Volume::factory()->create();
    $user = $collection->owner;

    $item = CollectionItem::factory()->create([
        'collection_id' => $collection->id,
        'volume_id' => $volume->id,
        'user_id' => $user->id,
        'status' => CollectionItemStatus::Owned,
        'purchase_price' => 8.50,
        'purchased_at' => '2026-01-15',
    ]);

    expect($item->collection->id)->toBe($collection->id)
        ->and($item->volume->id)->toBe($volume->id)
        ->and($item->user->id)->toBe($user->id)
        ->and($item->status)->toBe(CollectionItemStatus::Owned)
        ->and($item->purchase_price)->toBe('8.50')
        ->and($collection->items)->toHaveCount(1);
});
