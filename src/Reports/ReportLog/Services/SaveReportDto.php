<?php

namespace AMovil\Reports\ReportLog\Services;

use DateTime;

class SaveReportDto
{
    private $name;
    private $direccion;
    private $area;
    private $responsable;
    private $filename;
    private $fechaIni;
    private $fechaFin;
    private $estado;
    private $mensaje;
    private $tracName;
    private $input;

    public function __construct($name, $direccion, $area, $responsable, $filename, $fechaIni, $fechaFin, $estado, $mensaje, $tracName, $input)
    {
        $this->name = $name;
        $this->direccion = $direccion;
        $this->area = $area;
        $this->responsable = $responsable;
        $this->filename = $filename;
        $this->fechaIni = $fechaIni;
        $this->fechaFin = $fechaFin;
        $this->estado = $estado;
        $this->mensaje = $mensaje;
        $this->tracName = $tracName;
        $this->input = $input;
    }

    public static function create($name, $filename, $fechaIni, $fechaFin, $estado, $mensaje, $tracName, $input){
        return new self($name, null, null, null, $filename, $fechaIni, $fechaFin, $estado, $mensaje, $tracName, $input);
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDireccion(): ?string {
        return $this->direccion;
    }

    public function getArea(): ?string {
        return $this->area;
    }

    public function getResponsable(): ?string {
        return $this->responsable;
    }

    public function getFilename(): ?string {
        return $this->filename;
    }

    public function getFechaIni(): DateTime {
        return $this->fechaIni;
    }

    public function getFechaFin(): DateTime {
        return $this->fechaFin;
    }

    public function getEstado(): int {
        return $this->estado;
    }

    public function getMensaje(): ?string {
        return $this->mensaje;
    }

    public function getTracName(): ?string {
        return $this->tracName;
    }

    public function getInput(): ?string {
        return $this->input;
    }
}
