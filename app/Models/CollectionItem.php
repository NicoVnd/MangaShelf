<?php

namespace App\Models;

use App\Enums\CollectionItemStatus;
use Database\Factories\CollectionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collection_id
 * @property int $volume_id
 * @property int|null $user_id
 * @property CollectionItemStatus $status
 * @property string|null $purchase_price
 * @property Carbon|null $purchased_at
 * @property Carbon|null $read_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection $collection
 * @property-read Volume $volume
 * @property-read User|null $user
 */
#[Fillable([
    'collection_id',
    'volume_id',
    'user_id',
    'status',
    'purchase_price',
    'purchased_at',
    'read_at',
    'notes',
])]
class CollectionItem extends Model
{
    /** @use HasFactory<CollectionItemFactory> */
    use HasFactory;

    /**
     * The collection the item belongs to.
     *
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The volume linked to this item.
     *
     * @return BelongsTo<Volume, $this>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * The user who owns or is assigned to this item.
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
            'status' => CollectionItemStatus::class,
            'purchase_price' => 'decimal:2',
            'purchased_at' => 'date',
            'read_at' => 'date',
        ];
    }
}
