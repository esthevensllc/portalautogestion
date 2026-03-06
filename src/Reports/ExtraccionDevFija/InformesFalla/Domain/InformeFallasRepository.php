<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain;

use DateTime;

interface InformeFallasRepository
{
    public function createInformeFallas(string $numReporte, int $tipoReporte, string $filename, string $username, array $servicios);
    public function createInformeFallasCodcli(string $numReporte, array $codcli);
    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0);
    public function registerStatusChanges(string $numReporte, string $servicioAfectadoId, string $username, string $ipAddress, string $status, DateTime $fecha);
    public function updateStatusToRevisado(string $numReporte, int $servicioAfectadoId);
    public function updateStatusToAprobado(string $numReporte, int $servicioAfectadoId, string $ticket);
    public function updateStatusToDesaprobado(string $numReporte, int $servicioAfectadoId);
    public function updateStatusToEnEspera(string $numReporte, int $servicioAfectadoId);
    public function updateStatusToEnEjecucion(string $numReporte, int $servicioAfectadoId);
    public function updateStatusToEnEsperaEjecucion(string $numReporte, int $servicioAfectadoId);
    public function updateStatusToProcesado(string $numReporte, int $servicioAfectadoId);
    public function updateStatusToSinProcesar(string $numReporte, int $servicioAfectadoId);
    public function delete(string $numReporte, int $servicioAfectadoId);
    public function updateTicketToReporteProcesado(string $numReporte, int $servicioAfectadoId, $ticketAnterior, $ticketNuevo);
    public function updateNombreArchivo(string $numReporte, string $nombreReporte);
    public function getServiciosAfectados();
    public function findServicioAfectado(int $servicioAfectadoId);
}
