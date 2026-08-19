<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlMeteoFranceForecast;

final class ForecastHour
{
    public function __construct(
        public readonly int $timestamp,
        public readonly float $temperature,
        public readonly ?float $windchill,
        public readonly int $humidity,
        public readonly float $seaLevelPressure,
        public readonly float $windSpeed,
        public readonly float $windGust,
        public readonly int $windDirection,
        public readonly string $windDirectionLabel,
        public readonly float $rain,
        public readonly ?int $rainProbability,
        public readonly int $clouds,
        public readonly string $weatherIcon,
        public readonly string $weatherDescription,
    ) {
    }
}