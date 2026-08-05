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

    protected function normalizeImfSearchType(string $searchType): string
    {
        $normalizedType = strtolower(trim($searchType));

        if (!in_array($normalizedType, ['customer_id', 'telefono'], true)) {
            throw new InvalidArgumentException(
                'El tipo de búsqueda IMF FIJA debe ser Customer ID o Teléfono.'
            );
        }

        return $normalizedType;
    }

    protected function normalizeImfCustomerIdIdentifiers(string $customerId): array
    {
        $input = $this->validateTelefono($customerId);
        $compactValue = strtoupper((string) preg_replace('/\s+/', '', $input));

        if (!preg_match('/^H?[0-9]+$/', $compactValue)) {
            throw new InvalidArgumentException(
                'El Customer ID IMF FIJA debe contener solo números y opcionalmente el prefijo H.'
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

    protected function normalizeImrSearchType(string $searchType): string
    {
        $normalizedType = strtolower(trim($searchType));

        if (!in_array($normalizedType, ['customer_id', 'telefono'], true)) {
            throw new InvalidArgumentException(
                'El tipo de búsqueda IMR debe ser Customer ID o Teléfono.'
            );
        }

        return $normalizedType;
    }

    protected function normalizeImrPhoneIdentifiers(string $telefono): array
    {
        $input = $this->validateTelefono($telefono);

        if (!preg_match('/^\+?[0-9\s().-]+$/', $input)) {
            throw new InvalidArgumentException(
                'El teléfono IMR solo puede contener números, el prefijo +51 y separadores válidos.'
            );
        }

        $digits = preg_replace('/\D+/', '', $input) ?: '';

        if ($digits === '') {
            throw new InvalidArgumentException('Debe ingresar un teléfono IMR válido.');
        }

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

        $cargoFijo = $this->parseAmount($customerInfo['cargo_fijo'] ?? 0);
        $saldo = $cargoFijo - $importeTotal;

        return [
            'telefono' => $telefono,
            'customer_info' => $customerInfo,
            'history' => $history,
            'summary' => $summary,
            'totals' => [
                'cantidad_acciones' => $cantidadTotal,
                'importe_total' => $importeTotal,
                'cargo_fijo' => $cargoFijo,
                'saldo' => $saldo,
            ],
        ];
    }

    private function parseAmount($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $amount = trim((string) $value);

        if ($amount === '') {
            return 0.0;
        }

        $amount = preg_replace('/[^0-9,.-]/', '', $amount) ?: '0';
        $lastComma = strrpos($amount, ',');
        $lastDot = strrpos($amount, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $amount = str_replace($thousandSeparator, '', $amount);
            $amount = str_replace($decimalSeparator, '.', $amount);
        } elseif ($lastComma !== false) {
            $amount = str_replace(',', '.', $amount);
        }

        return is_numeric($amount) ? (float) $amount : 0.0;
    }
}

