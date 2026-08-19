<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlMeteoFranceForecast\ForecastDataset;
use LBonnefond\TrmnlMeteoFranceForecast\MeteoFranceForecastClient;
use LBonnefond\TrmnlMeteoFranceForecast\TrmnlForecastAdapter;

$client = new MeteoFranceForecastClient();

$response = $client->fetchForecast(
    latitude: 48.5839,
    longitude: 7.7455
);

$dataset = ForecastDataset::fromApiResponse($response);

$data = TrmnlForecastAdapter::fromDataset($dataset);

echo json_encode(
    $data,
    JSON_PRETTY_PRINT
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_THROW_ON_ERROR
);

echo PHP_EOL;