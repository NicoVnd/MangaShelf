<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property string|null $avatar
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EloquentCollection<int, Collection> $ownedCollections
 * @property-read EloquentCollection<int, CollectionMember> $collectionMemberships
 * @property-read EloquentCollection<int, Collection> $accessibleCollections
 * @property-read EloquentCollection<int, CollectionItem> $collectionItems
 */
#[Fillable(['name', 'email', 'role', 'avatar', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Collections owned by the user.
     *
     * @return HasMany<Collection, $this>
     */
    public function ownedCollections(): HasMany
    {
        return $this->hasMany(Collection::class, 'owner_id');
    }

    /**
     * Collection memberships where the user is a member.
     *
     * @return HasMany<CollectionMember, $this>
     */
    public function collectionMemberships(): HasMany
    {
        return $this->hasMany(CollectionMember::class);
    }

    /**
     * Shared collections accessible to the user via memberships.
     *
     * @return BelongsToMany<Collection, $this>
     */
    public function accessibleCollections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Items owned or assigned to this user in collections.
     *
     * @return HasMany<CollectionItem, $this>
     */
    public function collectionItems(): HasMany
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }
}
