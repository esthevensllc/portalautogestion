<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use App\Mail\NotificacionAprobado;
use Illuminate\Support\Facades\Mail;

class AprobarReport
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id,$ticket)
    {
        $data = $this->repo->aprobar($id,$ticket);

        if($data){
            $reportes = $this->repo->getReportesAprobados();
            $correo = new NotificacionAprobado($reportes);
            
            try {
                // Envío del correo
                Mail::to([
                    'C19884@claro.com.pe',
                    'C26282@claro.com.pe',
                    'ellanos@indracompany.com',
                    'C25976@claro.com.pe',
                    'josias.luna@claro.com.pe',
                    'omori@claro.com.pe',
                    'cpalacios@claro.com.pe',
                    'luyciana.rodriguez@claro.com.pe',
                    'C26311@claro.com.pe',
                    'marali.huaranca@claro.com.pe',
                    'pleon@claro.com.pe',
                    'C26559@claro.com.pe',
                ])->send($correo);
                //Mail::to(['ellanos@indracompany.com'])->send($correo);
            } catch (\Exception $e) {
                // Captura cualquier excepción generada durante el envío del correo
                return response()->json(['message' => 'Error al enviar el correo: '.$e->getMessage()], 500);
            }
        }

        return $data;
    }
}
