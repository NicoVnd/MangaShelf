<?php

use App\Jobs\ImportMangaJob;
use App\Jobs\SyncFollowedMangasJob;
use App\Jobs\SyncMangaVolumesJob;
use App\Models\Manga;
use App\Services\Manga\MangaImporter;
use App\Services\Manga\MangaMapper;
use Illuminate\Support\Facades\Queue;

test('manga mapper maps raw data to manga attributes correctly', function () {
    $mapper = new MangaMapper;

    $raw = [
        'mal_id' => 13,
        'title' => 'One Piece',
        'title_japanese' => 'ONE PIECE',
        'synopsis' => 'Test synopsis',
        'authors' => [['name' => 'Oda, Eiichiro']],
        'genres' => [['name' => 'Action'], ['name' => 'Aventure']],
        'status' => 'Publishing',
        'volumes' => null,
        'score' => 9.22,
        'images' => [
            'webp' => ['large_image_url' => 'https://example.com/cover.webp'],
        ],
    ];

    $attributes = $mapper->toMangaAttributes($raw);

    expect($attributes['external_id'])->toBe('13')
        ->and($attributes['title'])->toBe('One Piece')
        ->and($attributes['author'])->toBe('Oda, Eiichiro')
        ->and($attributes['genres'])->toBe(['Action', 'Aventure'])
        ->and($attributes['status'])->toBe('publishing')
        ->and($attributes['score'])->toBe(9.22)
        ->and($attributes['cover_url'])->toBe('https://example.com/cover.webp');
});

test('manga importer creates manga and default edition with volumes in database', function () {
    $importer = app(MangaImporter::class);

    $manga = $importer->importManga(13, translate: false);

    expect($manga)->toBeInstanceOf(Manga::class)
        ->and($manga->title)->toBe('One Piece')
        ->and($manga->external_id)->toBe('13');

    $this->assertDatabaseHas('mangas', [
        'external_id' => '13',
        'title' => 'One Piece',
    ]);
});

test('import manga job dispatches and runs through queue', function () {
    Queue::fake();

    ImportMangaJob::dispatch(13);

    Queue::assertPushed(ImportMangaJob::class, function ($job) {
        return $job->malId === 13;
    });
});

test('sync followed mangas job dispatches volume sync for all mangas', function () {
    Queue::fake();

    $manga = Manga::factory()->create(['external_id' => '999']);

    $job = new SyncFollowedMangasJob;
    $job->handle();

    Queue::assertPushed(SyncMangaVolumesJob::class, function ($job) use ($manga) {
        return $job->mangaId === $manga->id;
    });
});
