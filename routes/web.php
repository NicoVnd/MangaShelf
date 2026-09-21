<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CollectionItemController;
use App\Http\Controllers\CollectionMemberController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MangaController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/manga/{id}', [MangaController::class, 'show'])->name('mangas.show');
Route::get('/planning', [PlanningController::class, 'index'])->name('planning');
Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [CollectionController::class, 'index'])->name('dashboard');

    // Collections
    Route::resource('collections', CollectionController::class)->except(['index', 'create', 'edit']);
    Route::post('/collections/{collection}/add-manga', [CollectionController::class, 'addManga'])->name('collections.add-manga');

    // Collection Items
    Route::post('/collection-items', [CollectionItemController::class, 'store'])->name('collection-items.store');
    Route::post('/collection-items/toggle', [CollectionItemController::class, 'toggle'])->name('collection-items.toggle');
    Route::patch('/collection-items/{item}', [CollectionItemController::class, 'update'])->name('collection-items.update');
    Route::delete('/collection-items/{item}', [CollectionItemController::class, 'destroy'])->name('collection-items.destroy');

    // Collection Members & In-app Invitations
    Route::post('/collections/{collection}/members', [CollectionMemberController::class, 'store'])->name('collections.members.store');
    Route::post('/invitations/{invitation}/accept', [CollectionMemberController::class, 'accept'])->name('invitations.accept');
    Route::post('/invitations/{invitation}/decline', [CollectionMemberController::class, 'decline'])->name('invitations.decline');
    Route::patch('/collections/{collection}/members/{member}', [CollectionMemberController::class, 'update'])->name('collections.members.update');
    Route::delete('/collections/{collection}/members/{member}', [CollectionMemberController::class, 'destroy'])->name('collections.members.destroy');
});

require __DIR__.'/settings.php';
