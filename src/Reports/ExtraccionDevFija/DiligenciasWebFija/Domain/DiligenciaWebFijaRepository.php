<?php

namespace AMovil\Reports\ExtraccionDevFija\DiligenciasWebFija\Domain;

interface DiligenciaWebFijaRepository
{
    public function getReporteDiligenciasWebCorreo(array $tickets);
    public function getReporteDiligenciasWebDocumento(array $tickets);
    // public function getTicketsWithDevolucionWeb(array $tickets);
}
