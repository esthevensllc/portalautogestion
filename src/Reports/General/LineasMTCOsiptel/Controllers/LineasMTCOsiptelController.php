<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Controllers;

use AMovil\Reports\General\LineasMTCOsiptel\Services\CargarSMSLog;
use AMovil\Reports\General\LineasMTCOsiptel\Services\DeleteRegistrosSMS;
use AMovil\Reports\General\LineasMTCOsiptel\Services\ExportLineasMTCOsiptel;
use AMovil\Reports\General\LineasMTCOsiptel\Services\ExportLineasOsiptelMsisdnDni;
use AMovil\Reports\General\LineasMTCOsiptel\Services\GetProcessedTickets;
use AMovil\Reports\General\LineasMTCOsiptel\Services\GetProcessedTicketsMsisdn;
use AMovil\Reports\General\LineasMTCOsiptel\Services\DeleteRegistrosMsisdn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineasMTCOsiptelController
{
    private $exportLineas;
    private $exportLineasOsiptel;
    private $getTickets;
    private $cargarLog;
    private $deleteReport;
    private $getTicketsMsisdn;
    private $deleteReportMsisdn;

    public function __construct(
        ExportLineasMTCOsiptel $exportLineas,
        ExportLineasOsiptelMsisdnDni $exportLineasOsiptel,
        GetProcessedTickets $getTickets,
        CargarSMSLog $cargarSMSLog,
        DeleteRegistrosSMS $deleteReport,
        GetProcessedTicketsMsisdn $getTicketsMsisdn,
        DeleteRegistrosMsisdn $deleteReportMsisdn
    )
    {
        $this->exportLineas = $exportLineas;
        $this->exportLineasOsiptel = $exportLineasOsiptel;
        $this->getTickets = $getTickets;
        $this->cargarLog = $cargarSMSLog;
        $this->deleteReport = $deleteReport;
        $this->getTicketsMsisdn = $getTicketsMsisdn;
        $this->deleteReportMsisdn = $deleteReportMsisdn;
    }

    public function view()
    {
        $config = [
            "title" => "LINEAS PARA SMS",
            "url" => asset("lineas-mtc-osiptel/export"),
            "tipo_reporte" => [
                ["id" => 1, "label" => "Postpago PCM"],
                ["id" => 2, "label" => "Prepago PCM"],
                ["id" => 3, "label" => "Postpago OSIPTEL"],
                ["id" => 4, "label" => "Prepago OSIPTEL"],
            ]
        ];
        return view("lineas_mtc_osiptel.reportes", compact("config"));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '1800');

        $response = $this->exportLineas->__invoke(
            $request->input("ticket"),
            $request->input("tipo_reporte"),
            $request->input("step")
        )->data();

        $headers_type = [
            'csv' => [
                'Content-Encoding' => 'UTF-8',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            'zip' => [
                'Content-Type' => 'application/zip; charset=UTF-8',
                'Content-Transfer-Encoding' => 'Binary',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
        ];

        return response($response['content'], 200, $headers_type[$response['type']]);
    }

    
    public function logView()
    {
        $config = [
            "title" => "CARGAR SMS LOG",
            "url" => asset("lineas-mtc-osiptel/logs/save"),
            "ticket_url" => asset("lineas-mtc-osiptel/logs/tickets"),
            "tipo_plan" => [
                ["id" => "PREPAGO", "label" => "PREPAGO"],
                ["id" => "POSTPAGO", "label" => "POSTPAGO"],
            ],
            "tipo_solicitud" => [
                ["id" => "PCM", "label" => "PCM"],
                ["id" => "OSIPTEL", "label" => "OSIPTEL"],
            ],
        ];
        return view("lineas_mtc_osiptel.carga_sms_log", compact("config"));
    }

    public function saveLog(Request $request)
    {
        ini_set('max_execution_time', '7200');
        $response = $this->cargarLog->__invoke(
            $request->input("tipo_plan"),
            $request->input("tipo_solicitud"),
            $request->input("ticket"),
            $request->file("file")
        );
        return [];
    }

    public function getTickets($tipo_plan, $tipo_solicitud)
    {
        $data = $this->getTickets->__invoke($tipo_plan, $tipo_solicitud);
        return response()->json($data);
    }

    public function registrosView()
    {
        $config = [
            "title" => "REGISTROS DE SMS",
            "url" => asset("lineas-mtc-osiptel/registros-sms/search")
        ];
        return view("lineas_mtc_osiptel.registros_sms", compact("config"));
    }

    public function search()
    {
        $data = DB::connection("oracle")->table("USRAES.SMS_MTC_OSIPTEL_HIST")
        ->selectRaw("ticket, tipo, to_char(fecha, 'yyyy-mm-dd') fecha, num_post, num_recibidospost, num_norecibidospost, num_pre, num_recibidospre, num_norecibidospre")
        ->get();
        return response()->json([
            "data" => $data
        ]);
    }
    
    public function deleteView()
    {
        $config = [
            "title" => "ELIMINAR REGISTROS SMS",
            "api" => asset("lineas-mtc-osiptel/registros-sms/eliminar"),
            "ticket_url" => asset("lineas-mtc-osiptel/logs/tickets"),
            "tipo_solicitud" => [
                ["id" => "PCM", "label" => "PCM"],
                ["id" => "OSIPTEL", "label" => "OSIPTEL"],
            ],
        ];
        return view("lineas_mtc_osiptel.del_registro_sms", compact("config"));
    }

    public function delete(Request $request)
    {
        ini_set('max_execution_time', '7200');
        $this->deleteReport->__invoke(
            $request->input("ticket"),
            $request->input("tipo"),
            $request->input("fecha")
        );
        return response()->json([]);
    }

    public function cartaView()
    {
        $config = [
            "title" => "OSIPTEL (MSISDN,DNI)",
            "url" => asset("lineas-mtc-osiptel/osiptel-msisdn-dni/export"),
            "tipo_reporte" => [
                ["id" => "POSTPAGO", "label" => "Postpago OSIPTEL"],
                ["id" => "PREPAGO", "label" => "Prepago OSIPTEL"],
            ]
        ];
        return view("lineas_mtc_osiptel.osiptel_msisdn_dni", compact("config"));
    }

    public function procesarCarta(Request $request)
    {
        ini_set('max_execution_time', '3600');

        $response = $this->exportLineasOsiptel->__invoke(
            $request->input("ticket"),
            $request->input("tipo_reporte"),
            $request->input("step")
        );

        return $response;
    }

    public function registrosMsisdnView()
    {
        $config = [
            "title" => "REGISTROS DE OSIPTEL (MSISDN,DNI)",
            "url" => asset("lineas-mtc-osiptel/osiptel-registros-msisdn/searchMsisdn")
        ];
        return view("lineas_mtc_osiptel.registros_msisdn_dni", compact("config"));
    }

    public function searchMsisdn()
    {
        $data = DB::connection("oracle")->table("USRAES.SMS_OSIPTEL_V2_HIST")
        ->selectRaw("CARTA,FECHA,TIPO_PLAN,NUM_POST")
        ->get();
        return response()->json([
            "data" => $data
        ]);
    }

    public function deleteMsisdnView()
    {
        $config = [
            "title" => "ELIMINAR REGISTROS (MSISDN,DNI)",
            "api" => asset("lineas-mtc-osiptel/osiptel-registros-msisdn/eliminar"),
            "ticket_url" => asset("lineas-mtc-osiptel/logsMsisdn/tickets"),
            "tipo_solicitud" => [
                ["id" => "POSTPAGO", "label" => "Postpago OSIPTEL"],
                ["id" => "PREPAGO", "label" => "Prepago OSIPTEL"],
            ],
        ];
        return view("lineas_mtc_osiptel.del_registro_msisdn", compact("config"));
    }

    public function deleteMsisdn(Request $request)
    {
        ini_set('max_execution_time', '7200');
        $this->deleteReportMsisdn->__invoke(
            $request->input("ticket"),
            $request->input("tipo")
        );
        return response()->json([]);
    }

    
    public function getTicketsMsisdn($tipo_plan)
    {
        $data = $this->getTicketsMsisdn->__invoke($tipo_plan);
        return response()->json($data);
    }

}
