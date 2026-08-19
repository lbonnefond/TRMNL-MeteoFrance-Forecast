<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlMeteoFranceForecast;

use JsonException;
use RuntimeException;

final class MeteoFranceForecastClient
{
    private const API_BASE_URL = 'https://webservice.meteofrance.com';

    public function __construct(
        private readonly int $timeoutSeconds = 10
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchForecast(
        float $latitude,
        float $longitude,
        string $language = 'fr'
    ): array {
        $token = getenv('METEOFRANCE_API_TOKEN');

        if ($token === false || trim($token) === '') {
            throw new RuntimeException(
                'La variable d’environnement METEOFRANCE_API_TOKEN est absente.'
            );
        }

        $query = http_build_query(
            [
                'token' => $token,
                'lat' => $latitude,
                'lon' => $longitude,
                'lang' => $language,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $url = self::API_BASE_URL . '/forecast?' . $query;

        $curl = curl_init();

        if ($curl === false) {
            throw new RuntimeException(
                'Impossible d’initialiser cURL.'
            );
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_USERAGENT => 'TRMNL-MeteoFrance-Forecast/1.0',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
            ]
        );

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);

            throw new RuntimeException(
                'Erreur lors de l’appel à Météo-France : ' . $error
            );
        }

        $httpCode = (int) curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException(
                sprintf(
                    'Météo-France a renvoyé le code HTTP %d.',
                    $httpCode
                )
            );
        }

        try {
            $data = json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Réponse JSON Météo-France invalide.',
                0,
                $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'La réponse Météo-France n’est pas un objet JSON valide.'
            );
        }

        return $data;
    }
}