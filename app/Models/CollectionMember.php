<?php

namespace App\Models;

use App\Enums\CollectionMemberRole;
use Database\Factories\CollectionMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collection_id
 * @property int $user_id
 * @property CollectionMemberRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection $collection
 * @property-read User $user
 */
#[Fillable([
    'collection_id',
    'user_id',
    'role',
])]
class CollectionMember extends Model
{
    /** @use HasFactory<CollectionMemberFactory> */
    use HasFactory;

    /**
     * The collection the membership belongs to.
     *
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The user associated with the membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CollectionMemberRole::class,
        ];
    }
}
