<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Coordonnées GPS d'une adresse française, via l'API Adresse de l'État (gratuite, sans clé) :
 * https://adresse.data.gouv.fr/outils/api-doc/adresse
 */
class Geocoder
{
    /** Score minimal de confiance (0 à 1) pour retenir un résultat. */
    private const MIN_SCORE = 0.5;

    /** @return ?array{0: float, 1: float} latitude, longitude */
    public static function locate(string $query, ?string $postalCode = null): ?array
    {
        try {
            $response = Http::timeout(5)->acceptJson()->get('https://api-adresse.data.gouv.fr/search/', array_filter([
                'q' => $query,
                'postcode' => $postalCode,
                'limit' => 1,
            ]));
        } catch (\Throwable $e) {
            Log::info('Géocodage impossible', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }

        $feature = $response->json('features.0');
        if (! $response->successful() || ! $feature || ($feature['properties']['score'] ?? 0) < self::MIN_SCORE) {
            return null;
        }

        [$lng, $lat] = $feature['geometry']['coordinates'];

        return [(float) $lat, (float) $lng];
    }
}
