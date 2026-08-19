<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlMeteoFranceForecast\ForecastDataset;
use LBonnefond\TrmnlMeteoFranceForecast\MeteoFranceForecastClient;

$client = new MeteoFranceForecastClient();

$data = $client->fetchForecast(
    latitude: 48.5839,
    longitude: 7.7455
);

$dataset = ForecastDataset::fromApiResponse($data);

echo sprintf(
    "%s (%.4f, %.4f) — %d m\n",
    $dataset->locationName,
    $dataset->latitude,
    $dataset->longitude,
    $dataset->altitude
);

echo "Timezone: {$dataset->timezone}\n";
echo "Prévisions horaires: " . count($dataset->hours) . "\n";
echo "Prévisions quotidiennes: " . count($dataset->days) . "\n";

echo "\n=== PREMIERES ECHEANCES ===\n";

foreach (array_slice($dataset->hours, 0, 8) as $hour) {
    echo sprintf(
        "%s | %4.1f °C | %-15s | pluie %.1f mm | prob. %s%%\n",
        date('Y-m-d H:i', $hour->timestamp),
        $hour->temperature,
        $hour->weatherDescription,
        $hour->rain,
        $hour->rainProbability === null
            ? '-'
            : (string) $hour->rainProbability
    );
}

echo "\n=== JOURS ===\n";

foreach (array_slice($dataset->days, 0, 5) as $day) {
    echo sprintf(
        "%s | %4.1f / %4.1f °C | %-20s | %.1f mm\n",
        date('Y-m-d', $day->timestamp),
        $day->temperatureMin,
        $day->temperatureMax,
        $day->weatherDescription,
        $day->precipitation
    );
}