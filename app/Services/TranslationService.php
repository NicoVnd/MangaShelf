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

        $cacheKey = 'trans_fr_v2_'.md5($cleanText);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($cleanText, $text) {
            // 1. Essai prioritaire : Google Translate GTX (gratuit, ultra-rapide, sans limite 500 chars)
            try {
                $response = Http::withoutVerifying()
                    ->timeout(6)
                    ->get('https://translate.googleapis.com/translate_a/single', [
                        'client' => 'gtx',
                        'sl' => 'auto',
                        'tl' => 'fr',
                        'dt' => 't',
                        'q' => mb_substr($cleanText, 0, 2500),
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json) && isset($json[0]) && is_array($json[0])) {
                        $fullTranslated = '';
                        foreach ($json[0] as $segment) {
                            if (isset($segment[0]) && is_string($segment[0])) {
                                $fullTranslated .= $segment[0];
                            }
                        }

                        if (! empty(trim($fullTranslated))) {
                            return html_entity_decode($fullTranslated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Log and continue to fallback
                Log::debug('Google Translate GTX failed, falling back to MyMemory: '.$e->getMessage());
            }

            // 2. Fallback : MyMemory API
            try {
                $chunk = mb_substr($cleanText, 0, 500);
                $response = Http::withoutVerifying()
                    ->timeout(6)
                    ->get('https://api.mymemory.translated.net/get', [
                        'q' => $chunk,
                        'langpair' => 'en|fr',
                    ]);

                if ($response->successful()) {
                    $translated = $response->json('responseData.translatedText');
                    if ($translated && is_string($translated) && ! str_contains($translated, 'MYMEMORY WARNING')) {
                        return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Translation fallback failed: '.$e->getMessage());
            }

            return $text;
        });
    }
}
