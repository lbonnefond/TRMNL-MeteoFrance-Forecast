<?php

declare(strict_types=1);

namespace LBonnefond\TrmnlMeteoFranceForecast;

use JsonException;
use RuntimeException;

final class ForecastCache
{
    public function __construct(
        private readonly string $directory,
        private readonly int $ttlSeconds = 900
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(
        float $latitude,
        float $longitude
    ): ?array {
        $filename = $this->getFilename(
            $latitude,
            $longitude
        );

        if (!is_file($filename)) {
            return null;
        }

        $mtime = filemtime($filename);

        if ($mtime === false) {
            return null;
        }

        if ((time() - $mtime) > $this->ttlSeconds) {
            return null;
        }

        $json = file_get_contents($filename);

        if ($json === false) {
            return null;
        }

        try {
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }

        return is_array($data)
            ? $data
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function put(
        float $latitude,
        float $longitude,
        array $data
    ): void {
        $this->ensureDirectoryExists();

        $filename = $this->getFilename(
            $latitude,
            $longitude
        );

        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        $temporaryFilename = $filename
            . '.tmp.'
            . bin2hex(random_bytes(4));

        $written = file_put_contents(
            $temporaryFilename,
            $json,
            LOCK_EX
        );

        if ($written === false) {
            throw new RuntimeException(
                'Impossible d’écrire le cache météo.'
            );
        }

        if (!rename($temporaryFilename, $filename)) {
            @unlink($temporaryFilename);

            throw new RuntimeException(
                'Impossible de finaliser le cache météo.'
            );
        }
    }

    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (
            !mkdir(
                $this->directory,
                0775,
                true
            )
            && !is_dir($this->directory)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de créer le répertoire de cache "%s".',
                    $this->directory
                )
            );
        }
    }

    private function getFilename(
        float $latitude,
        float $longitude
    ): string {
        $key = sprintf(
            '%.4f_%.4f',
            $latitude,
            $longitude
        );

        $key = str_replace(
            ['-', '.'],
            ['m', '_'],
            $key
        );

        return rtrim(
            $this->directory,
            DIRECTORY_SEPARATOR
        )
            . DIRECTORY_SEPARATOR
            . 'forecast_'
            . $key
            . '.json';
    }
}