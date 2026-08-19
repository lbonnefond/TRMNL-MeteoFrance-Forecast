<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlMeteoFranceForecast;

use RuntimeException;

final class ForecastDataset
{
    /**
     * @param list<ForecastHour> $hours
     * @param list<ForecastDay> $days
     */
    public function __construct(
        public readonly string $locationName,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $altitude,
        public readonly string $timezone,
        public readonly int $updatedAt,
        public readonly array $hours,
        public readonly array $days,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApiResponse(array $data): self
    {
        $position = $data['position'] ?? null;

        if (!is_array($position)) {
            throw new RuntimeException(
                'Réponse Météo-France invalide : position absente.'
            );
        }

        $probabilities = self::buildProbabilityIndex(
            $data['probability_forecast'] ?? []
        );

        $hours = [];

        foreach ($data['forecast'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $timestamp = (int) ($item['dt'] ?? 0);

            if ($timestamp <= 0) {
                continue;
            }

            $rain = is_array($item['rain'] ?? null)
                ? $item['rain']
                : [];

            /*
             * Météo-France change progressivement la résolution :
             * 1 h, puis 3 h, puis 6 h.
             *
             * On récupère donc la quantité correspondant à la période
             * effectivement présente dans la réponse.
             */
            $rainAmount = self::extractPrecipitationAmount($rain);

            $hours[] = new ForecastHour(
                timestamp: $timestamp,
                temperature: (float) ($item['T']['value'] ?? 0.0),
                windchill: isset($item['T']['windchill'])
                    ? (float) $item['T']['windchill']
                    : null,
                humidity: (int) ($item['humidity'] ?? 0),
                seaLevelPressure: (float) ($item['sea_level'] ?? 0.0),
                windSpeed: (float) ($item['wind']['speed'] ?? 0.0),
                windGust: (float) ($item['wind']['gust'] ?? 0.0),
                windDirection: (int) ($item['wind']['direction'] ?? 0),
                windDirectionLabel: (string) ($item['wind']['icon'] ?? ''),
                rain: $rainAmount,
                rainProbability: $probabilities[$timestamp] ?? null,
                clouds: (int) ($item['clouds'] ?? 0),
                weatherIcon: (string) ($item['weather']['icon'] ?? ''),
                weatherDescription: (string) ($item['weather']['desc'] ?? ''),
            );
        }

        $days = [];

        foreach ($data['daily_forecast'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $timestamp = (int) ($item['dt'] ?? 0);

            if ($timestamp <= 0) {
                continue;
            }

            $days[] = new ForecastDay(
                timestamp: $timestamp,
                temperatureMin: (float) ($item['T']['min'] ?? 0.0),
                temperatureMax: (float) ($item['T']['max'] ?? 0.0),
                humidityMin: (int) ($item['humidity']['min'] ?? 0),
                humidityMax: (int) ($item['humidity']['max'] ?? 0),
                precipitation: (float) ($item['precipitation']['24h'] ?? 0.0),
                uv: (int) ($item['uv'] ?? 0),
                weatherIcon: (string) ($item['weather12H']['icon'] ?? ''),
                weatherDescription: (string) ($item['weather12H']['desc'] ?? ''),
                sunrise: (int) ($item['sun']['rise'] ?? 0),
                sunset: (int) ($item['sun']['set'] ?? 0),
            );
        }

        return new self(
            locationName: (string) ($position['name'] ?? ''),
            latitude: (float) ($position['lat'] ?? 0.0),
            longitude: (float) ($position['lon'] ?? 0.0),
            altitude: (int) ($position['alti'] ?? 0),
            timezone: (string) ($position['timezone'] ?? 'Europe/Paris'),
            updatedAt: (int) ($data['updated_on'] ?? 0),
            hours: $hours,
            days: $days,
        );
    }

    /**
     * @param mixed $raw
     * @return array<int, int>
     */
    private static function buildProbabilityIndex(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $result = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $timestamp = (int) ($item['dt'] ?? 0);

            if ($timestamp <= 0) {
                continue;
            }

            $rain = $item['rain'] ?? null;

            if (!is_array($rain)) {
                continue;
            }

            /*
             * À courte échéance, préférer la probabilité 3 h.
             * Plus loin, Météo-France ne fournit souvent que 6 h.
             */
            $probability = $rain['3h'] ?? $rain['6h'] ?? null;

            if ($probability !== null) {
                $result[$timestamp] = (int) $probability;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $rain
     */
    private static function extractPrecipitationAmount(array $rain): float
    {
        foreach (['1h', '3h', '6h'] as $period) {
            if (isset($rain[$period])) {
                return (float) $rain[$period];
            }
        }

        return 0.0;
    }
}