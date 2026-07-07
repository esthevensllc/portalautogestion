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

    protected function buildResponse(string $telefono, array $history, array $summary): array
    {
        $cantidadTotal = array_reduce($summary, function ($carry, array $row) {
            return $carry + (int) $row['cantidad'];
        }, 0);

        $importeTotal = array_reduce($summary, function ($carry, array $row) {
            return $carry + (float) $row['importe'];
        }, 0.0);

        return [
            'telefono' => $telefono,
            'history' => $history,
            'summary' => $summary,
            'totals' => [
                'cantidad_acciones' => $cantidadTotal,
                'importe_total' => round($importeTotal, 2),
            ],
        ];
    }
}
