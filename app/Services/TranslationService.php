<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Translate English synopsis/text to French with caching.
     */
    public function translateToFrench(?string $text): ?string
    {
        if (! $text || trim($text) === '') {
            return null;
        }

        // Nettoyer les mentions types [Written by MAL Rewrite]
        $cleanText = preg_replace('/\[Written by MAL Rewrite\].*$/is', '', $text);
        $cleanText = trim((string) $cleanText);

        if (empty($cleanText)) {
            return $text;
        }

        $cacheKey = 'trans_fr_'.md5($cleanText);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($cleanText, $text) {
            try {
                // Découper si trop long pour l'API MyMemory (max ~500 chars recommandés par bloc)
                $chunk = mb_substr($cleanText, 0, 500);

                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->get('https://api.mymemory.translated.net/get', [
                        'q' => $chunk,
                        'langpair' => 'en|fr',
                    ]);

                if ($response->successful()) {
                    $translated = $response->json('responseData.translatedText');
                    if ($translated && is_string($translated) && ! str_contains($translated, 'MYMEMORY WARNING')) {
                        // Nettoyer entités HTML comme &#39; &quot;
                        return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Translation failed: '.$e->getMessage());
            }

            return $text;
        });
    }
}
