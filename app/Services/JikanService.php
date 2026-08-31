<?php

namespace App\Services;

use App\Models\Manga;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JikanService
{
    public function __construct(
        protected TranslationService $translator
    ) {}

    /**
     * Fallback popular manga dataset in French.
     */
    protected array $fallbackPopular = [
        [
            'external_id' => '13',
            'title' => 'One Piece',
            'original_title' => 'ONE PIECE',
            'synopsis' => 'Gol D. Roger, le Roi des Pirates, est exécuté par le Gouvernement Mondial. Avant de mourir, il révèle l\'existence d\'un fabuleux trésor, le One Piece, caché sur Grand Line. Vingt-deux ans plus tard, le jeune Monkey D. Luffy prend la mer pour devenir à son tour le Roi des Pirates.',
            'author' => 'Oda, Eiichiro',
            'genres' => ['Action', 'Aventure', 'Fantastique'],
            'status' => 'publishing',
            'total_volumes' => null,
            'score' => 9.22,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/2/253146l.webp',
        ],
        [
            'external_id' => '2',
            'title' => 'Berserk',
            'original_title' => 'ベルセルク',
            'synopsis' => 'Guts, un guerrier solitaire et redoutable portant une gigantesque épée et une armure noire, erre dans un monde médiéval impitoyable et sanglant, animé par une soif inextinguible de vengeance contre les monstres et son ancien compagnon Griffith.',
            'author' => 'Miura, Kentarou',
            'genres' => ['Action', 'Aventure', 'Dark Fantasy'],
            'status' => 'publishing',
            'total_volumes' => 42,
            'score' => 9.47,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/1/157897l.webp',
        ],
        [
            'external_id' => '25',
            'title' => 'Fullmetal Alchemist',
            'original_title' => '鋼の錬金術師',
            'synopsis' => 'Dans un monde où l\'alchimie est une science régie par la loi de l\'Échange Équivalent, deux jeunes frères alchimistes, Edward et Alphonse Elric, tentent désespérément de ressusciter leur défunte mère et en paient le prix fort en perdant leurs corps.',
            'author' => 'Arakawa, Hiromu',
            'genres' => ['Action', 'Aventure', 'Drame', 'Fantastique'],
            'status' => 'finished',
            'total_volumes' => 27,
            'score' => 9.03,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/3/243675l.webp',
        ],
        [
            'external_id' => '44',
            'title' => 'Vagabond',
            'original_title' => 'バガボンド',
            'synopsis' => 'Dans le Japon féodal du XVIe siècle, Shinmen Takezō est un jeune homme sauvage et impulsif. Après avoir survécu à la bataille de Sekigahara, il entame un long voyage initiatique pour devenir le plus grand sabreur sous le nom de Miyamoto Musashi.',
            'author' => 'Inoue, Takehiko',
            'genres' => ['Action', 'Aventure', 'Historique'],
            'status' => 'on_hiatus',
            'total_volumes' => 37,
            'score' => 9.24,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/1/259070l.webp',
        ],
        [
            'external_id' => '11',
            'title' => 'Naruto',
            'original_title' => 'NARUTO -ナルト-',
            'synopsis' => 'Naruto Uzumaki est un jeune orphelin farceur du village ninja de Konoha qui porte en lui le Démon Renard à Neuf Queues. Bien que craint et rejeté par les villageois, il s\'entraîne sans relâche pour réaliser son rêve : devenir Hokage.',
            'author' => 'Kishimoto, Masashi',
            'genres' => ['Action', 'Aventure', 'Arts Martiaux'],
            'status' => 'finished',
            'total_volumes' => 72,
            'score' => 8.08,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/3/117681l.webp',
        ],
        [
            'external_id' => '1',
            'title' => 'Monster',
            'original_title' => 'MONSTER',
            'synopsis' => 'Kenzō Tenma, brillant neurochirurgien japonais exerçant en Allemagne, choisit un soir d\'opérer en urgence un jeune garçon blessé par balle plutôt que le maire de la ville. Des années plus tard, ce garçon resurgit comme un tueur en série machiavélique.',
            'author' => 'Urasawa, Naoki',
            'genres' => ['Drame', 'Mystère', 'Psychologique', 'Thriller'],
            'status' => 'finished',
            'total_volumes' => 18,
            'score' => 9.15,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/3/258224l.webp',
        ],
        [
            'external_id' => '21',
            'title' => 'Death Note',
            'original_title' => 'DEATH NOTE',
            'synopsis' => 'Light Yagami, brillant lycéen japonais désabusé, ramasse un étrange carnet noir tombé du ciel : le Death Note. Toute personne dont le nom est inscrit dans ce carnet meurt. Light décide d\'éliminer tous les criminels pour créer un monde parfait.',
            'author' => 'Ohba, Tsugumi, Obata, Takeshi',
            'genres' => ['Surnaturel', 'Thriller', 'Psychologique'],
            'status' => 'finished',
            'total_volumes' => 12,
            'score' => 8.68,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/2/54453l.webp',
        ],
        [
            'external_id' => '113138',
            'title' => 'Jujutsu Kaisen',
            'original_title' => '呪術廻戦',
            'synopsis' => 'Yūji Itadori, lycéen doté d\'une force physique exceptionnelle, avale par accident un doigt calciné du Roi des Fléaux, Ryōmen Sukuna. Il est alors initié aux arcanes de l\'exorcisme pour traquer les malédictions menaçant l\'humanité.',
            'author' => 'Akutami, Gege',
            'genres' => ['Action', 'Dark Fantasy', 'Surnaturel'],
            'status' => 'finished',
            'total_volumes' => 30,
            'score' => 8.44,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/3/210341l.webp',
        ],
        [
            'external_id' => '116778',
            'title' => 'Chainsaw Man',
            'original_title' => 'チェンソーマン',
            'synopsis' => 'Denji est un adolescent écrasé par les dettes de son père qui survit en chassant des démons aux côtés de Pochita, son chien-démon tronçonneuse. Après avoir été trahi, il renaît sous la forme d\'un hybride homme-démon doté de tronçonneuses.',
            'author' => 'Fujimoto, Tatsuki',
            'genres' => ['Action', 'Horreur', 'Surnaturel'],
            'status' => 'publishing',
            'total_volumes' => null,
            'score' => 8.51,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/3/216464l.webp',
        ],
        [
            'external_id' => '119161',
            'title' => 'Spy x Family',
            'original_title' => 'SPY×FAMILY',
            'synopsis' => 'Pour accomplir une mission d\'espionnage cruciale visant à maintenir la paix entre deux nations ennemies, le maître espion Twilight doit fonder une fausse famille en adoptant une petite fille télépathe et en épousant une tueuse à gages.',
            'author' => 'Endo, Tatsuya',
            'genres' => ['Action', 'Comédie', 'Espionnage'],
            'status' => 'publishing',
            'total_volumes' => null,
            'score' => 8.52,
            'cover_url' => 'https://cdn.myanimelist.net/images/manga/3/219741l.webp',
        ],
    ];

    /**
     * Get popular manga in French.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPopularMangas(int $limit = 12): array
    {
        return Cache::remember("jikan_popular_mangas_fr_v3_{$limit}", now()->addHours(6), function () use ($limit) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'MangaShelf/1.0',
                    ])
                    ->timeout(12)
                    ->get('https://api.jikan.moe/v4/manga', [
                        'order_by' => 'popularity',
                        'sort' => 'asc',
                        'limit' => $limit,
                        'sfw' => 'true',
                    ]);

                if ($response->successful()) {
                    $items = $response->json('data') ?? [];
                    if (! empty($items)) {
                        return array_map(fn ($item) => $this->formatMangaData($item), $items);
                    }
                }

                Log::warning("Jikan API popular request returned status {$response->status()}");
            } catch (\Throwable $e) {
                Log::error("Jikan API popular request error: {$e->getMessage()}");
            }

            return array_slice($this->fallbackPopular, 0, $limit);
        });
    }

    /**
     * Get specific manga by MAL ID with French translation.
     *
     * @return array<string, mixed>|null
     */
    public function getMangaById(int|string $malId): ?array
    {
        return Cache::remember("jikan_manga_fr_{$malId}", now()->addHours(24), function () use ($malId) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'MangaShelf/1.0',
                    ])
                    ->timeout(15)
                    ->get("https://api.jikan.moe/v4/manga/{$malId}");

                if ($response->successful()) {
                    $data = $response->json('data');
                    if ($data) {
                        return $this->formatMangaData($data);
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Jikan API error for ID {$malId}: {$e->getMessage()}");
            }

            return null;
        });
    }

    /**
     * Search manga by keyword with French translation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchManga(string $query, int $limit = 12): array
    {
        $cacheKey = 'jikan_search_fr_'.md5(strtolower(trim($query))."_limit_{$limit}");

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($query, $limit) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'MangaShelf/1.0',
                    ])
                    ->timeout(15)
                    ->get('https://api.jikan.moe/v4/manga', [
                        'q' => $query,
                        'limit' => $limit,
                        'sfw' => 'true',
                    ]);

                if ($response->successful()) {
                    $items = $response->json('data') ?? [];

                    return array_map(fn ($item) => $this->formatMangaData($item), $items);
                }
            } catch (\Throwable $e) {
                Log::error("Jikan API search error: {$e->getMessage()}");
            }

            return array_values(array_filter(
                $this->fallbackPopular,
                fn ($m) => str_contains(strtolower($m['title']), strtolower($query))
                    || str_contains(strtolower($m['author'] ?? ''), strtolower($query))
            ));
        });
    }

    /**
     * Import or get Manga in French in the local database.
     */
    public function importOrGetManga(int|string $malId): ?Manga
    {
        $formatted = $this->getMangaById($malId);
        if (! $formatted) {
            return null;
        }

        return Manga::updateOrCreate(
            ['external_id' => (string) $malId],
            $formatted
        );
    }

    /**
     * Format Jikan payload into French attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function formatMangaData(array $data): array
    {
        $authors = [];
        if (! empty($data['authors'])) {
            foreach ($data['authors'] as $author) {
                $authors[] = $author['name'];
            }
        }

        $genres = [];
        if (! empty($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                $genres[] = $genre['name'];
            }
        }

        $coverUrl = $data['images']['webp']['large_image_url']
            ?? $data['images']['jpg']['large_image_url']
            ?? $data['images']['jpg']['image_url']
            ?? null;

        // Synopsis en français
        $synopsis = $data['synopsis'] ?? null;
        if ($synopsis) {
            $synopsis = $this->translator->translateToFrench($synopsis);
        }

        return [
            'external_id' => (string) $data['mal_id'],
            'title' => $data['title'],
            'original_title' => $data['title_japanese'] ?? null,
            'synopsis' => $synopsis,
            'author' => ! empty($authors) ? implode(', ', $authors) : null,
            'genres' => $genres,
            'status' => isset($data['status']) ? strtolower($data['status']) : null,
            'total_volumes' => $data['volumes'] ?? null,
            'score' => $data['score'] ?? null,
            'cover_url' => $coverUrl,
        ];
    }
}
