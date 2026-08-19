<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlMeteoFranceForecast\ForecastDataset;
use LBonnefond\TrmnlMeteoFranceForecast\MeteoFranceForecastClient;
use LBonnefond\TrmnlMeteoFranceForecast\TrmnlForecastAdapter;
use LBonnefond\TrmnlMeteoFranceForecast\ForecastCache;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $latitude = filter_input(
        INPUT_GET,
        'lat',
        FILTER_VALIDATE_FLOAT
    );

    $longitude = filter_input(
        INPUT_GET,
        'lon',
        FILTER_VALIDATE_FLOAT
    );

    if ($latitude === false || $latitude === null) {
        throw new InvalidArgumentException(
            'Paramètre "lat" absent ou invalide.'
        );
    }

    if ($longitude === false || $longitude === null) {
        throw new InvalidArgumentException(
            'Paramètre "lon" absent ou invalide.'
        );
    }

    if ($latitude < -90.0 || $latitude > 90.0) {
        throw new InvalidArgumentException(
            'Le paramètre "lat" doit être compris entre -90 et 90.'
        );
    }

    if ($longitude < -180.0 || $longitude > 180.0) {
        throw new InvalidArgumentException(
            'Le paramètre "lon" doit être compris entre -180 et 180.'
        );
    }

    $latitude = (float) $latitude;
    $longitude = (float) $longitude;

    $cache = new ForecastCache(
        directory: dirname(__DIR__) . '/var/cache',
        ttlSeconds: 900
    );

    $data = $cache->get(
        latitude: $latitude,
        longitude: $longitude
    );

    if ($data === null) {
        $client = new MeteoFranceForecastClient();

        $response = $client->fetchForecast(
            latitude: $latitude,
            longitude: $longitude
        );

        $dataset = ForecastDataset::fromApiResponse(
            $response
        );

        $data = TrmnlForecastAdapter::fromDataset(
            $dataset
        );

        $cache->put(
            latitude: $latitude,
            longitude: $longitude,
            data: $data
        );
    }

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
    );
} catch (InvalidArgumentException $exception) {
    http_response_code(400);

    echo json_encode(
        [
            'error' => true,
            'message' => $exception->getMessage(),
        ],
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $exception) {
    http_response_code(502);

    echo json_encode(
        [
            'error' => true,
            'message' => 'Impossible de récupérer les prévisions Météo-France.',
        ],
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    error_log(
        sprintf(
            '[TRMNL-MeteoFrance-Forecast] %s: %s',
            $exception::class,
            $exception->getMessage()
        )
    );
}
