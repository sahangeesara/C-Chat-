<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'open_meteo_geocoding' => [
        'base_url' => env('OPEN_METEO_GEOCODING_URL', 'https://geocoding-api.open-meteo.com'),
    ],

    'open_meteo_weather' => [
        'base_url' => env('OPEN_METEO_WEATHER_URL', 'https://api.open-meteo.com'),
    ],

    'mapsco' => [
        'base_url' => env('MAPSCO_BASE_URL', 'https://geocode.maps.co'),
        'api_key' => env('MAPSCO_API_KEY'),
    ],

];
