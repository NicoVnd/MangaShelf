<?php

namespace App\Models;

use Database\Factories\MangaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string $title
 * @property string|null $original_title
 * @property string|null $synopsis
 * @property string|null $author
 * @property string|null $status
 * @property int|null $total_volumes
 * @property float|null $score
 * @property array<int, string>|null $genres
 * @property string|null $cover_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EloquentCollection<int, Edition> $editions
 * @property-read EloquentCollection<int, Volume> $volumes
 */
#[Fillable([
    'external_id',
    'title',
    'original_title',
    'synopsis',
    'author',
    'status',
    'total_volumes',
    'score',
    'genres',
    'cover_url',
])]
class Manga extends Model
{
    /** @use HasFactory<MangaFactory> */
    use HasFactory;

    /**
     * Get the editions for the manga.
     *
     * @return HasMany<Edition, $this>
     */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }

    /**
     * Get all volumes of this manga across editions.
     *
     * @return HasManyThrough<Volume, Edition, $this>
     */
    public function volumes(): HasManyThrough
    {
        return $this->hasManyThrough(Volume::class, Edition::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_volumes' => 'integer',
            'score' => 'decimal:2',
            'genres' => 'array',
        ];
    }
}
