<?php

namespace App\Models;

use Database\Factories\VolumeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $edition_id
 * @property string $number
 * @property string|null $title
 * @property string|null $isbn10
 * @property string|null $isbn13
 * @property Carbon|null $publication_date
 * @property int|null $page_count
 * @property string|null $cover_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Edition $edition
 * @property-read EloquentCollection<int, CollectionItem> $collectionItems
 */
#[Fillable([
    'edition_id',
    'number',
    'title',
    'isbn10',
    'isbn13',
    'publication_date',
    'page_count',
    'cover_url',
])]
class Volume extends Model
{
    /** @use HasFactory<VolumeFactory> */
    use HasFactory;

    /**
     * Get the edition that owns the volume.
     *
     * @return BelongsTo<Edition, $this>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * Get the collection items referencing this volume.
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
            'publication_date' => 'date',
            'page_count' => 'integer',
        ];
    }
}
