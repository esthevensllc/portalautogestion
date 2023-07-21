<?php

namespace AMovil\Reports\SolicitudDatosConfirmacion\Services;

use AMovil\Reports\SolicitudDatosConfirmacion\Domain\SolicitudDatosConfirmacionRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Auth\User\Domain\UserRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class SolicitudDatosConfirmacionService
{
    private $repo;
    private $exportService;
    private $log;
    private $authService;
    private $userRepository;

    public function __construct(SolicitudDatosConfirmacionRepository $repo, ExportService $exportService,ReportLogRepository $log,AuthService $authService, UserRepository $userRepository)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->log = $log;
        $this->authService = $authService;
        $this->userRepository = $userRepository;
    }

    public function getTable($filter1,$filter2,$filter3,$filter4){
        $data = $this->repo->getTable($filter1,$filter2,$filter3,$filter4);
        return $data;
    }

    public function export($filter1,$filter2,$filter3,$filter4): Response
    {
        $dt_start = new DateTime();
        $fecha = Carbon::now()->format('Ymd');
        try {
            $data = $this->repo->export($filter1,$filter2,$filter3,$filter4);            

            $options = [
                //"rowType" => "Array",
                "sheetIndex" => 0,
                'title' => "DATOS TITULAR",
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                        'borders'=> [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => array('rgb'=>'000000')
                            ]
                        ]
                    ],
                    'body' => [
                        'font' => ['size' => 9]
                    ]
                ]
            ];

            $headers = [];

            foreach($filter4 as $key => $val){
                if($val == 'id_card_value'){
                    $headers["numero_de_documento"]["label"] = "numero_de_documento";
                }
                if($val == 'id_card_type_value'){
                    $headers["tipo_de_documento"]["label"] = "tipo_de_documento";
                }
                if($val == 'customer_full_name'){
                    $headers["nombre_cliente"]["label"] = "nombre_cliente";
                }
                if($val == 'agreement_mode'){
                    $headers["modalidad_del_servicio"]["label"] = "modalidad_del_servicio";
                }
                if($val == 'subscription_access_number'){
                    $headers["numero_contratado"]["label"] = "numero_contratado";
                }
                if($val == 'agreement_status'){
                    $headers["estado_del_servicio"]["label"] = "estado_del_servicio";
                }
                if($val == 'subscription_start_date'){
                    $headers["fecha_alta"]["label"] = "fecha_alta";
                }
                if($val == 'subscription_end_date'){
                    $headers["fecha_baja"]["label"] = "fecha_baja";
                }
                if($val == 'subscription_status_date'){
                    $headers["fecha_ultimo_cambio_estado"]["label"] = "fecha_ultimo_cambio_estado";
                }
            }

            $this->exportService->loadData($headers, $data, $options);
            $exportContent = $this->exportService->getWriter(WriterType::CSV)->getOutput();

            $this->reportLog($fecha , $dt_start, new DateTime(), 1,['mensaje' => 'se exporto correctamente']);

            return new Response([], [
                "filename" => "DATOS_TITULAR_".$fecha.".csv",
                "type" => "csv",
                "content" => $exportContent,
            ]);

        } catch (\Throwable $th) {
            $this->reportLog($fecha, $dt_start, new DateTime(), 0,['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function reportLog($fecha, DateTime $ini, DateTime $fin, $estado,$mensaje)
    {

        $userIdentifier = $this->authService->getUserIdentifier();
        $user = $this->userRepository->findByIdentifier($userIdentifier);
        $contacto = null;
        $direccion = null;
        $area = null;
        if($user !== null){
            $contacto = "{$user->name} {$user->last_name}";
            $direccion = $user->direccion;
            $area = $user->area;
        }

        $this->log->save(
            'SOLICITUD DE DATOS/CONFIRMACION',
            $direccion,
            $area,
            $contacto,
            $userIdentifier,
            "DIEGO MORENO",
            "DATOS_TITULAR_".$fecha.".csv",
            DateTime::createFromFormat("Y-m-d H:i:s", $ini->format('Y-m-d H:i:s')),
            DateTime::createFromFormat("Y-m-d H:i:s", $fin->format('Y-m-d H:i:s')),
            0,
            $estado,
            $mensaje['mensaje'],
            'solicitud-datos-confirmacion'
        );
    } 
}