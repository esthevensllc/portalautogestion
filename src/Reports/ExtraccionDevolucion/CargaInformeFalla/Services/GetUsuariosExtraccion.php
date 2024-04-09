<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InforFallasRepository;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use DateTime;

class GetUsuariosExtraccion
{
    private $repo;
    private $infoFallasRepo;
    
    public function __construct(ExtraccionRepository $repo, InforFallasRepository $infoFallasRepo)
    {
        $this->repo = $repo;
        $this->infoFallasRepo = $infoFallasRepo;
    }

    public function __invoke($num_reporte, $departamento): Response
    {
        $input = $this->infoFallasRepo->getInputByNumReporte_Departamento($num_reporte, $departamento);
        $numUsuariosAfectados1 = null;
        $numUsuariosAfectados2 = null;
        $numUsuariosAfectados3 = null;
        if($input !== null){
            $numUsuariosAfectados1 = $this->getNumUsuariosAfectados($input, $input->ticket, 100);
            $numUsuariosAfectados2 = $this->getNumUsuariosAfectados($input, $input->ticket, 120);
            $numUsuariosAfectados3 = $this->getNumUsuariosAfectados($input, $input->ticket, 140);
        }
        $data = [
            "input" => $input,
            "usuarios" => [
                ["num_usuarios" => $numUsuariosAfectados1, "minutos" => 100],
                ["num_usuarios" => $numUsuariosAfectados2, "minutos" => 120],
                ["num_usuarios" => $numUsuariosAfectados3, "minutos" => 140],
            ]
        ];
        $data = json_decode(json_encode($data));
        return new Response([], $data);
    }

    public function getInputs($num_reporte, $departamento): Response
    {
        $input = $this->infoFallasRepo->getInputByNumReporte_Departamento($num_reporte, $departamento);
        $input = json_decode(json_encode($input));
        return new Response([], $input);
    }

    private function getNumUsuariosAfectados($input, $ticket, $minutos_usuarios)
    {
        $arrayDistritos = [];
        foreach($input->distritos as $row){
            $arrayDistritos[] = [$row->departamento, $row->provincia, $row->distrito];
        }
        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $input->corte_fecha_ini);
        $dtFechaIni = (clone $dtFechaFin)->modify("-{$minutos_usuarios} minute");

        $usuariosAfectados = $this->repo->getReporte(
            $input->celdas,
            $arrayDistritos,
            $dtFechaIni,
            DateTime::createFromFormat("Y-m-d H:i:s", $input->fecha_fin),
            $ticket,
            DateTime::createFromFormat("Y-m-d H:i:s", $input->fecha_interes),
            DateTime::createFromFormat("Y-m-d H:i:s", $input->corte_fecha_ini),
            DateTime::createFromFormat("Y-m-d H:i:s", $input->corte_fecha_fin)
        );
        return $usuariosAfectados;
    }
}
