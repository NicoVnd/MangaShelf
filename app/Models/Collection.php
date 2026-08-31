<?php

namespace App\Models;

use App\Enums\CollectionType;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property CollectionType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read EloquentCollection<int, CollectionMember> $members
 * @property-read EloquentCollection<int, User> $users
 * @property-read EloquentCollection<int, CollectionItem> $items
 */
#[Fillable([
    'owner_id',
    'name',
    'type',
])]
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    /**
     * The owner of the collection.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Memberships associated with the collection.
     *
     * @return HasMany<CollectionMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(CollectionMember::class);
    }

    /**
     * Users who are members of this collection.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Items in this collection.
     *
     * @return HasMany<CollectionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CollectionItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CollectionType::class,
        ];
    }
}
