<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlMeteoFranceForecast;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class TrmnlForecastAdapter
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDataset(
        ForecastDataset $dataset,
        ?int $now = null
    ): array {
        if ($dataset->hours === []) {
            throw new RuntimeException(
                'Impossible de générer les données TRMNL : aucune prévision.'
            );
        }

        $timezone = new DateTimeZone($dataset->timezone);
        $now ??= time();

        /*
         * L'API Météo-France ne fournit pas ici une observation
         * "current" distincte.
         *
         * On utilise donc l'échéance de prévision la plus proche
         * de l'heure actuelle.
         */
        $current = self::findClosestHour(
            $dataset->hours,
            $now
        );

        /*
         * Données horaires.
         */
        $hourlyTime = [];
        $hourlyTemperature = [];
        $hourlyWeatherCode = [];
        $hourlyWeatherDescription = [];
        $hourlyIsDay = [];

        foreach ($dataset->hours as $hour) {
            $hourlyTime[] = self::formatDateTime(
                $hour->timestamp,
                $timezone
            );

            $hourlyTemperature[] = $hour->temperature;

            $hourlyWeatherCode[] = $hour->weatherIcon;

            $hourlyWeatherDescription[] =
                $hour->weatherDescription;

            $hourlyIsDay[] = self::isDay(
                $hour->timestamp,
                $dataset->days
            );
        }

        /*
         * Données quotidiennes.
         */
        $dailyTime = [];
        $dailyWeatherCode = [];
        $dailyWeatherDescription = [];
        $dailySunrise = [];
        $dailySunset = [];
        $dailyTemperatureMax = [];
        $dailyTemperatureMin = [];

        foreach ($dataset->days as $day) {
            $dailyTime[] = self::formatDate(
                $day->timestamp,
                $timezone
            );

            $dailyWeatherCode[] = $day->weatherIcon;

            $dailyWeatherDescription[] =
                $day->weatherDescription;

            $dailySunrise[] = self::formatDateTime(
                $day->sunrise,
                $timezone
            );

            $dailySunset[] = self::formatDateTime(
                $day->sunset,
                $timezone
            );

            $dailyTemperatureMax[] =
                $day->temperatureMax;

            $dailyTemperatureMin[] =
                $day->temperatureMin;
        }

        return [
            'latitude' => $dataset->latitude,
            'longitude' => $dataset->longitude,
            'elevation' => $dataset->altitude,
            'timezone' => $dataset->timezone,

            'current' => [
                'time' => self::formatDateTime(
                    $current->timestamp,
                    $timezone
                ),
                'temperature_2m' =>
                    $current->temperature,
                'is_day' => self::isDay(
                    $current->timestamp,
                    $dataset->days
                ),
                'weather_code' =>
                    $current->weatherIcon,
                'weather_description' =>
                    $current->weatherDescription,
            ],

            'hourly' => [
                'time' => $hourlyTime,
                'temperature_2m' =>
                    $hourlyTemperature,
                'weather_code' =>
                    $hourlyWeatherCode,
                'weather_description' =>
                    $hourlyWeatherDescription,
                'is_day' =>
                    $hourlyIsDay,
            ],

            'daily' => [
                'time' => $dailyTime,
                'weather_code' =>
                    $dailyWeatherCode,
                'weather_description' =>
                    $dailyWeatherDescription,
                'sunrise' =>
                    $dailySunrise,
                'sunset' =>
                    $dailySunset,
                'temperature_2m_max' =>
                    $dailyTemperatureMax,
                'temperature_2m_min' =>
                    $dailyTemperatureMin,
            ],
        ];
    }

    /**
     * @param list<ForecastHour> $hours
     */
    private static function findClosestHour(
        array $hours,
        int $timestamp
    ): ForecastHour {
        $closest = $hours[0];

        $closestDifference = abs(
            $closest->timestamp - $timestamp
        );

        foreach ($hours as $hour) {
            $difference = abs(
                $hour->timestamp - $timestamp
            );

            if ($difference < $closestDifference) {
                $closest = $hour;
                $closestDifference = $difference;
            }
        }

        return $closest;
    }

    /**
     * @param list<ForecastDay> $days
     */
    private static function isDay(
        int $timestamp,
        array $days
    ): int {
        foreach ($days as $day) {
            if (
                $timestamp >= $day->sunrise
                && $timestamp < $day->sunset
            ) {
                return 1;
            }
        }

        return 0;
    }

    private static function formatDateTime(
        int $timestamp,
        DateTimeZone $timezone
    ): string {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($timezone)
            ->format('Y-m-d\TH:i');
    }

    private static function formatDate(
        int $timestamp,
        DateTimeZone $timezone
    ): string {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($timezone)
            ->format('Y-m-d');
    }
}