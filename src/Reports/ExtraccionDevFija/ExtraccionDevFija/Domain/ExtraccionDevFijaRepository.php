<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain;

use DateTime;

interface ExtraccionDevFijaRepository
{
    public function process($distritos, $ticket, $servicioAfectado, DateTime $fechaIni, DateTime $fechaFin, $mesesInteres);
    public function countReportByTicketDepartamento($ticket);
    public function findInputsByNumReporteAndTicket($numReporte, $ticket);
    public function getInputs();
    public function getReporteUsuariosAfectados($ticket);
    public function getReportePostpago($ticket);
    public function createInput(
        string $numReporte,
        string $username,
        string $servicioAfectado,
        DateTime $fechaIni,
        DateTime $fechaFin,
        int $mesesInteres,
        string $departamento,
        string $provincia,
        string $distrito,
        array $planos
    );
    public function createPlanoInput(
        string $numReporte,
        string $departamento,
        string $provincia,
        string $distrito,
        array $planos
    );
    public function createServicioAfectadoInput(
        string $numReporte,
        string $username,
        int $servicioAfectadoId,
        DateTime $fechaIni,
        DateTime $fechaFin,
        int $mesesInteres
    );
    public function updateTicketServicioInputByNumReporte(string $numReporte, int $servicioAfectadoId, ?string $ticket);
    public function deleteServicioInput(string $numReporte, int $servicioAfectadoId);
    public function deletePlanoInput(string $numReporte);
}
