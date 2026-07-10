<?php

namespace AMovil\Reports\IMRIMF\Services;

use InvalidArgumentException;

trait ConcernsSearchResponse
{
    protected function validateTelefono(string $telefono): string
    {
        $telefono = trim($telefono);

        if ($telefono === '') {
            throw new InvalidArgumentException('Debe ingresar un teléfono o Customer ID.');
        }

        if (strlen($telefono) > 30) {
            throw new InvalidArgumentException('El teléfono o Customer ID no debe superar 30 caracteres.');
        }

        return $telefono;
    }

    /**
     * Genera los dos formatos requeridos por las consultas IMF.
     *
     * Entradas admitidas:
     * - 953468136
     * - 51953468136
     * - +51953468136
     *
     * Resultado:
     * - customer_info: 51953468136 (DS_SUSCRIPTORES)
     * - actions:       953468136   (historial y resumen)
     */
    protected function normalizeImfIdentifiers(string $telefono): array
    {
        $input = $this->validateTelefono($telefono);

        if (!preg_match('/^\+?[0-9\s().-]+$/', $input)) {
            throw new InvalidArgumentException(
                'El teléfono IMF solo puede contener números, el prefijo +51 y separadores válidos.'
            );
        }

        $digits = preg_replace('/\D+/', '', $input) ?: '';

        if ($digits === '') {
            throw new InvalidArgumentException('Debe ingresar un teléfono IMF válido.');
        }

        // Historial y resumen trabajan sin el código de país 51.
        $telefonoSinPrefijo = substr($digits, 0, 2) === '51'
            ? substr($digits, 2)
            : $digits;

        if ($telefonoSinPrefijo === '') {
            throw new InvalidArgumentException('Debe ingresar el número después del prefijo 51.');
        }

        return [
            'input' => $input,
            'customer_info' => '51' . $telefonoSinPrefijo,
            'actions' => $telefonoSinPrefijo,
        ];
    }

    /**
     * Genera los dos formatos requeridos por las consultas IMR.
     *
     * Entradas admitidas:
     * - 16805491
     * - H16805491
     * - h16805491
     *
     * Resultado:
     * - customer_info: 16805491  (DS_SUSCRIPTORES.CUENTA_CD)
     * - actions:       H16805491 (historial y resumen)
     */
    protected function normalizeImrIdentifiers(string $customerId): array
    {
        $input = $this->validateTelefono($customerId);
        $compactValue = strtoupper((string) preg_replace('/\s+/', '', $input));

        if (!preg_match('/^H?[0-9]+$/', $compactValue)) {
            throw new InvalidArgumentException(
                'El Customer ID IMR debe contener solo números y opcionalmente el prefijo H.'
            );
        }

        $customerIdSinPrefijo = substr($compactValue, 0, 1) === 'H'
            ? substr($compactValue, 1)
            : $compactValue;

        if ($customerIdSinPrefijo === '') {
            throw new InvalidArgumentException('Debe ingresar el número después del prefijo H.');
        }

        return [
            'input' => $input,
            'customer_info' => $customerIdSinPrefijo,
            'actions' => 'H' . $customerIdSinPrefijo,
        ];
    }

    protected function buildResponse(
        string $telefono,
        array $history,
        array $summary,
        ?array $customerInfo = null
    ): array {
        $cantidadTotal = array_reduce($summary, function ($carry, array $row) {
            return $carry + (int) $row['cantidad'];
        }, 0);

        $importeTotal = array_reduce($summary, function ($carry, array $row) {
            return $carry + (float) $row['importe'];
        }, 0.0);

        return [
            'telefono' => $telefono,
            'customer_info' => $customerInfo,
            'history' => $history,
            'summary' => $summary,
            'totals' => [
                'cantidad_acciones' => $cantidadTotal,
                'importe_total' => round($importeTotal, 2),
            ],
        ];
    }
}
