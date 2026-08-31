<?php

namespace App\Http\Controllers;

use App\Services\JikanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home page with live popular mangas or search results from Jikan API.
     */
    public function __invoke(Request $request, JikanService $jikan): Response
    {
        $search = $request->input('search');

        if ($search) {
            $mangas = $jikan->searchManga($search, limit: 12);
        } else {
            $mangas = $jikan->getPopularMangas();
        }

        return Inertia::render('Welcome', [
            'mangas' => $mangas,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
