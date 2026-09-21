<?php

namespace App\Http\Controllers;

use App\Models\Manga;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Display the search page with optional query results.
     */
    public function index(Request $request): Response
    {
        $query = $request->input('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            $results = Manga::query()
                ->where('title', 'LIKE', "%{$query}%")
                ->orWhere('original_title', 'LIKE', "%{$query}%")
                ->orWhere('author', 'LIKE', "%{$query}%")
                ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', ["{$query}%"])
                ->limit(50)
                ->get(['id', 'external_id', 'title', 'original_title', 'author', 'cover_url', 'status', 'total_volumes', 'score', 'genres'])
                ->map(fn (Manga $m) => [
                    'id' => $m->id,
                    'external_id' => $m->external_id,
                    'title' => $m->title,
                    'original_title' => $m->original_title,
                    'author' => $m->author,
                    'cover_url' => $m->cover_url,
                    'status' => $m->status,
                    'total_volumes' => $m->total_volumes,
                    'score' => $m->score,
                    'genres' => $m->genres,
                ]);
        }

        // Popular series to show when query is empty
        $popular = [];
        if (strlen($query) < 2) {
            $popular = Manga::query()
                ->whereNotNull('score')
                ->orderByDesc('score')
                ->limit(24)
                ->get(['id', 'external_id', 'title', 'cover_url', 'score', 'total_volumes', 'author'])
                ->map(fn (Manga $m) => [
                    'id' => $m->id,
                    'external_id' => $m->external_id,
                    'title' => $m->title,
                    'cover_url' => $m->cover_url,
                    'score' => $m->score,
                    'total_volumes' => $m->total_volumes,
                    'author' => $m->author,
                ]);
        }

        return Inertia::render('Search', [
            'query' => $query,
            'results' => $results,
            'popular' => $popular,
        ]);
    }
}
