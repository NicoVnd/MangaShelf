<?php

namespace App\Models;

use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $manga_id
 * @property string $publisher
 * @property string $language
 * @property string|null $collection_name
 * @property string|null $format
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manga $manga
 * @property-read EloquentCollection<int, Volume> $volumes
 */
#[Fillable([
    'manga_id',
    'publisher',
    'language',
    'collection_name',
    'format',
])]
class Edition extends Model
{
    /** @use HasFactory<EditionFactory> */
    use HasFactory;

    /**
     * Get the manga that owns the edition.
     *
     * @return BelongsTo<Manga, $this>
     */
    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    /**
     * Get the volumes for the edition.
     *
     * @return HasMany<Volume, $this>
     */
    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }
}
