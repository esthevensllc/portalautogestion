<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetDepartamentosByNumReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetReportesByCriteria;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetUsuariosExtraccion;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use Illuminate\Http\Request;

class UsuarioMinutosController
{
    private $getUsuariosExtraccion;
    private $getDepartamentos;
    private $getReportesByCriteria;
    private $processExtraccion;

    public function __construct(
        GetUsuariosExtraccion $getUsuariosExtraccion,
        GetDepartamentosByNumReporte $getDepartamentos,
        GetReportesByCriteria $getReportesByCriteria,
        ProcessExtraccion $processExtraccion
    ) {
        $this->getUsuariosExtraccion = $getUsuariosExtraccion;
        $this->getDepartamentos = $getDepartamentos;
        $this->getReportesByCriteria = $getReportesByCriteria;
        $this->processExtraccion = $processExtraccion;
    }

    public function view()
    {
        $reports = $this->getReportesByCriteria->__invoke([
            ["revisado", 1],
            ["aprobado", 1],
            ["procesado", 0],
        ])->data();

        $config = [
            "title" => "USUARIOS X MINUTOS",
            "getUsuariosApi" => url("extraccion-devolucion/usuario-minuto/usuarios-minutos"),
            "getDepartamentosApi" => url("extraccion-devolucion/usuario-minuto/departamentos"),
            "processApi" => url("extraccion-devolucion/usuario-minuto/process"),
            "reports" => $reports
        ];
        return view("extraccion_devolucion.usuario_minuto", compact("config"));
    }

    public function getDepartamentosByNumReporte(Request $request)
    {
        $response = $this->getDepartamentos->__invoke(
            $request->get("num_reporte")
        )->data();
        return response()->json($response);
    }

    public function getUsuariosByNumReporte(Request $request)
    {
        $usuariosminutos = $this->getUsuariosExtraccion->__invoke(
            $request->get("num_reporte"),
            $request->get("departamento")
        )->data();
        return response()->json($usuariosminutos);
    }

    public function processExtraccion(Request $request)
    {
        $num_reporte = $request->input("num_reporte");
        $departamento = $request->input("departamento");
        $minutos_usuarios = $request->input("minutos_usuarios");

        $input = $this->getUsuariosExtraccion->getInputs($num_reporte, $departamento)->data();
        $distritos = [];
        foreach($input->distritos as $row){
            $distritos[] = "{$row->departamento},{$row->provincia},{$row->distrito}";
        }
        $this->processExtraccion->__invoke(
            1,
            1,
            implode(",", $input->celdas),
            implode("\n", $distritos),
            null,
            null,
            null,
            $input->ticket,
            ProcessExtraccion::MESES_INTERES, //$input->fecha_interes,
            $input->corte_fecha_ini,
            $input->corte_fecha_fin,
            $minutos_usuarios
        );
        $response = $this->processExtraccion->__invoke(
            2,
            1,
            implode(",", $input->celdas),
            implode("\n", $distritos),
            null,
            null,
            null,
            $input->ticket,
            ProcessExtraccion::MESES_INTERES, //$input->fecha_interes,
            $input->corte_fecha_ini,
            $input->corte_fecha_fin,
            $minutos_usuarios
        )->toArray();
        return response()->json($response);
    }
}
