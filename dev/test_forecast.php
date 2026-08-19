<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LBonnefond\TrmnlMeteoFranceForecast\MeteoFranceForecastClient;

$client = new MeteoFranceForecastClient();

try {
    $forecast = $client->fetchForecast(
        latitude: 48.5839,
        longitude: 7.7455
    );

    echo "=== TOP LEVEL ===\n";
    print_r(array_keys($forecast));

    echo "\n=== POSITION ===\n";
    print_r($forecast['position'] ?? null);

    echo "\n=== UPDATED_ON ===\n";
    print_r($forecast['updated_on'] ?? null);

    echo "\n=== DAILY_FORECAST ===\n";
    echo json_encode(
        array_slice($forecast['daily_forecast'] ?? [], 0, 3),
        JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
    echo "\n";

    echo "\n=== FIRST FORECAST ENTRIES ===\n";
    echo json_encode(
        array_slice($forecast['forecast'] ?? [], 0, 6),
        JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
    echo "\n";

    echo "\n=== FIRST PROBABILITY ENTRIES ===\n";
    echo json_encode(
        array_slice($forecast['probability_forecast'] ?? [], 0, 6),
        JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
    echo "\n";

    echo PHP_EOL;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "%s: %s\n",
            $exception::class,
            $exception->getMessage()
        )
    );

    exit(1);
}
