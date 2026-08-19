<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlMeteoFranceForecast;

final class ForecastDay
{
    public function __construct(
        public readonly int $timestamp,
        public readonly float $temperatureMin,
        public readonly float $temperatureMax,
        public readonly int $humidityMin,
        public readonly int $humidityMax,
        public readonly float $precipitation,
        public readonly int $uv,
        public readonly string $weatherIcon,
        public readonly string $weatherDescription,
        public readonly int $sunrise,
        public readonly int $sunset,
    ) {
    }
}