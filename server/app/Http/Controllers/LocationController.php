<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    public function weather(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'current' => 'nullable|string|max:120',
            'timezone' => 'nullable|string|max:64',
        ]);

        $response = Http::timeout(8)
            ->acceptJson()
            ->get(rtrim(config('services.open_meteo_weather.base_url'), '/') . '/v1/forecast', [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'current' => $validated['current'] ?? 'temperature_2m,weather_code',
                'timezone' => $validated['timezone'] ?? 'auto',
            ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Weather service is unavailable.',
                'status' => $response->status(),
            ], 502);
        }

        $payload = $response->json();

        return response()->json([
            'source' => 'open-meteo',
            'raw' => $payload,
            'current' => $payload['current'] ?? null,
            'current_units' => $payload['current_units'] ?? null,
            'timezone' => $payload['timezone'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
        ]);
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'language' => 'nullable|string|max:10',
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $language = $validated['language'] ?? 'en';

        $openMeteo = Http::timeout(8)
            ->acceptJson()
            ->get(rtrim(config('services.open_meteo_geocoding.base_url'), '/') . '/v1/reverse', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'language' => $language,
                'format' => 'json',
            ]);

        if ($openMeteo->successful()) {
            $results = $openMeteo->json('results', []);
            $first = is_array($results) && !empty($results) ? $results[0] : null;

            return response()->json([
                'source' => 'open-meteo',
                'raw' => $openMeteo->json(),
                'location' => [
                    'name' => $first['name'] ?? null,
                    'admin1' => $first['admin1'] ?? null,
                    'country' => $first['country'] ?? null,
                    'latitude' => $first['latitude'] ?? $latitude,
                    'longitude' => $first['longitude'] ?? $longitude,
                ],
            ]);
        }

        $fallbackParams = [
            'lat' => $latitude,
            'lon' => $longitude,
        ];

        $mapsCoApiKey = config('services.mapsco.api_key');
        if (!empty($mapsCoApiKey)) {
            $fallbackParams['api_key'] = $mapsCoApiKey;
        }

        $fallback = Http::timeout(8)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => config('app.name', 'Laravel') . '/chatapp',
            ])
            ->get(rtrim(config('services.mapsco.base_url'), '/') . '/reverse', $fallbackParams);

        if ($fallback->successful()) {
            $payload = $fallback->json();
            $address = $payload['address'] ?? [];

            return response()->json([
                'source' => 'maps.co',
                'raw' => $payload,
                'location' => [
                    'name' => $payload['display_name'] ?? null,
                    'admin1' => $address['state'] ?? $address['county'] ?? null,
                    'country' => $address['country'] ?? null,
                    'latitude' => isset($payload['lat']) ? (float) $payload['lat'] : $latitude,
                    'longitude' => isset($payload['lon']) ? (float) $payload['lon'] : $longitude,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Reverse geocoding service is unavailable.',
            'open_meteo_status' => $openMeteo->status(),
            'fallback_status' => $fallback->status(),
        ], 502);
    }
}

