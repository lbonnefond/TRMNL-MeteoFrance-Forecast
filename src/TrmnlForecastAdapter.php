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
        $hourlyWeatherCodeMeteoFrance = [];
        $hourlyWeatherDescription = [];
        $hourlyIsDay = [];

        foreach ($dataset->hours as $hour) {
            $hourlyTime[] = self::formatDateTime(
                $hour->timestamp,
                $timezone
            );

            $hourlyTemperature[] = $hour->temperature;

            $hourlyWeatherCode[] = self::meteofranceToWmo(
                $hour->weatherIcon
            );

            $hourlyWeatherCodeMeteoFrance[] =
                $hour->weatherIcon;

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
        $dailyWeatherCodeMeteoFrance = [];
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

            $dailyWeatherCode[] = self::meteofranceToWmo(
                $day->weatherIcon
            );

            $dailyWeatherCodeMeteoFrance[] =
                $day->weatherIcon;

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
                    self::meteofranceToWmo(
                        $current->weatherIcon
                    ),
                'weather_code_meteofrance' =>
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
                'weather_code_meteofrance' =>
                    $hourlyWeatherCodeMeteoFrance,
                'weather_description' =>
                    $hourlyWeatherDescription,
                'is_day' =>
                    $hourlyIsDay,
            ],

            'daily' => [
                'time' => $dailyTime,
                'weather_code' =>
                    $dailyWeatherCode,
                'weather_code_meteofrance' =>
                    $dailyWeatherCodeMeteoFrance,
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
     * Convertit un code d'icône Météo-France en code météo WMO.
     *
     * Le but n'est pas de reconstruire exactement la classification
     * météorologique WMO, mais de fournir un code compatible avec
     * iconsmapping.v1.json utilisé par le plugin TRMNL original.
     *
     * Les suffixes j/n indiquent le jour ou la nuit et ne modifient
     * pas le phénomène météorologique. Le choix de l'icône jour/nuit
     * est effectué séparément grâce à is_day.
     */
    private static function meteofranceToWmo(
        string $code
    ): int {
        /*
         * Retire le suffixe jour/nuit.
         *
         * Exemples :
         * p1j      -> p1
         * p2bisn   -> p2bis
         * p14bisj  -> p14bis
         */
        $baseCode = preg_replace(
            '/[jn]$/',
            '',
            strtolower(trim($code))
        );

        if ($baseCode === null) {
            return 3;
        }

        return match ($baseCode) {
            /*
             * Ciel clair / ensoleillé.
             */
            'p1' => 0,

            /*
             * Éclaircies / variable.
             */
            'p2',
            'p2bis' => 2,

            /*
             * Très nuageux / couvert.
             */
            'p3',
            'p3bis',
            'p4' => 3,

            /*
             * Brouillard.
             */
            'p5',
            'p6',
            'p7' => 45,

            /*
             * Bruine / faibles précipitations.
             */
            'p8',
            'p9',
            'p10' => 51,

            /*
             * Pluie faible / averses faibles.
             */
            'p11',
            'p12',
            'p12bis',
            'p13',
            'p13bis' => 61,

            /*
             * Pluie / averses.
             */
            'p14',
            'p14bis',
            'p15',
            'p15bis' => 63,

            /*
             * Pluie forte / fortes averses.
             */
            'p16',
            'p16bis' => 65,

            /*
             * Neige faible / averses de neige faibles.
             */
            'p17',
            'p17bis',
            'p18',
            'p18bis' => 71,

            /*
             * Neige.
             */
            'p19',
            'p19bis',
            'p20',
            'p20bis' => 73,

            /*
             * Neige forte.
             */
            'p21',
            'p21bis' => 75,

            /*
             * Pluie et neige mêlées.
             */
            'p22',
            'p22bis',
            'p23',
            'p23bis' => 85,

            /*
             * Orages.
             */
            'p24',
            'p25',
            'p26',
            'p26bis',
            'p27',
            'p27bis' => 95,

            /*
             * Orages forts / grêle.
             */
            'p28',
            'p28bis',
            'p29',
            'p29bis',
            'p30',
            'p30bis' => 99,

            /*
             * Code inconnu : couvert constitue un fallback
             * visuellement neutre.
             */
            default => 3,
        };
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