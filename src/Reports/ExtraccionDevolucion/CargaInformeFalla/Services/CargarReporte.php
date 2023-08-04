<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Mail\NotificacionCarga;
use Exception;
use Illuminate\Support\Facades\Mail;

class CargarReporte
{
    private $repo;
    private $fechaInteresNumber = ProcessExtraccion::MESES_INTERES;
    private $minutosUsuarios = ProcessExtraccion::MINUTOS_USUARIOS;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($numero, $excel, $detalleExtraccion)
    {
        $filename = $excel->getClientOriginalName();
        $reporte = $this->repo->updateReporte($numero, $excel->getClientOriginalName());
        if($reporte){
            foreach($detalleExtraccion as $row){
                $this->saveInputs(
                    $numero,
                    explode(",", $row["celdas"]),
                    explode("\n", str_replace(["\t","\r"], ["",""], $row["distritos"])),
                    $row["corteFechaIni"],
                    $row["corteFechaFin"]
                );
            }

            $excel->storeAs('carga_informe_falla', $numero.'_'.$excel->getClientOriginalName());
            // $reportes = $this->repo->getReportesSnRevisado();
            $reportes = $this->repo->findInputFor($numero);
            $correo = new NotificacionCarga($reportes);
            $correo->setSubject("CARGA DE INFORMES DE FALLAS - {$filename}");
            
            try {
                // Envío del correo
                Mail::to([
                    'C19884@claro.com.pe',
                    'C26282@claro.com.pe',
                    'ellanos@indracompany.com',
                    'lizeth.moya@claro.com.pe',
                    'carlos.malpartida@claro.com.pe',
                    'bryan.robles@claro.com.pe',
                    'Noc-claro@claro.com.pe',
                    'cpalacios@claro.com.pe',
                    'cdiazb@claro.com.pe',
                ])->send($correo);
                // Mail::to(['cclinarez@indracompany.com','C26282@claro.com.pe'])->send($correo);
            } catch (\Exception $e) {
                // Captura cualquier excepción generada durante el envío del correo
                return response()->json(['message' => 'Error al enviar el correo: '.$e->getMessage()], 500);
            }
        }
        return $reporte;
    }

    private function saveInputs($numero, $celdas, $provincias, $corteFechaIni, $corteFechaFin)
    {
        $arrayDistritos = [];
        foreach($provincias as $row){
            $arrayDistritos[] = explode(",", $row);
        }

        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
        $dtFechaIni = (clone $dtFechaFin)->modify("-{$this->minutosUsuarios} minute");

        $dtFechaInteres = new DateTime();
        $dtFechaInteres->modify("+{$this->fechaInteresNumber} month");
        $dtCorteFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
        $dtCorteFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaFin);

        $this->repo->saveReportInputs(
            $numero,
            $celdas,
            $arrayDistritos,
            $dtFechaIni,
            $dtFechaFin,
            null,
            $dtFechaInteres,
            $dtCorteFechaIni,
            $dtCorteFechaFin
        );
    }

}
