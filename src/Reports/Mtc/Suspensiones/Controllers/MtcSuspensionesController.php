<?php

namespace AMovil\Reports\Mtc\Suspensiones\Controllers;

use AMovil\Reports\Mtc\Suspensiones\Services\ExportReporteSuspensiones;
use AMovil\Shared\Exports\Domain\WriterType;
use Illuminate\Http\Request;
use DB;

class MtcSuspensionesController
{
    private ExportReporteSuspensiones $service;

    public function __construct(ExportReporteSuspensiones $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $reportes = [
            ['id' => '1', 'label' => 'Suspensiones'],
            ['id' => '2', 'label' => 'Titularidad'],
            ['id' => '3', 'label' => 'Mensual'],
            ['id' => '4', 'label' => 'Notificaciones'],
        ];
        return view('mtc.suspensiones', compact('reportes'));
    }

    public function export(Request $request)
    {
        //mb_internal_encoding('UTF-8');
        
        // Esto le dice a PHP que generaremos cadenas UTF-8
        //mb_http_output('UTF-8');
        // Ocurrió

        // ini_set('default_charset', '');
        // mb_http_output('pass');
        // mb_detect_order(["UTF-8"]);

        $tipo_reporte = $request->input('tipo_reporte');
        $file1 = $request->file('file1')->getPathname();
        $filename = $request->file('file1')->getClientOriginalName();
        $response = $this->service->__invoke($tipo_reporte, ['all_filename' => $file1, 'filename' => $filename])->data();

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

        $headers = $headers_type[$response['type']];
        if(array_key_exists('message', $response)){
            $headers['Custom-message'] = $response['message'];
        }
        
        return response($response['content'], 200, $headers);
    }

    public function exportTest()
    {
        $data = \DB::select(DB::raw("SELECT A.*,B.ASNC_TIPO_TITULAR ASNC_TIPO_TITULAR,B.ASNC_TIPO_CONTRATO   ASNC_TIPO_CONTRATO,B.ASNC_TIPO_DOCTITULAR   ASNC_TIPO_DOCTITULAR,
                B.ASNV_NUM_DOCTITULAR   ASNV_NUM_DOCTITULAR,B.ASNV_NOMBRETITULAR    ASNV_NOMBRETITULAR,B.ASND_FEC_INICONTRATO   ASND_FEC_INICONTRATO,
                B.ASND_FEC_FINCONTRATO  ASND_FEC_FINCONTRATO,B.ASNV_DIRECCION   ASNV_DIRECCION,B.ASNV_UBIGEO    ASNV_UBIGEO,NULL    ASND_FECHAHORA_LLAMADA
                FROM usraes.TMP_PLANTILLA_TITU A
                LEFT JOIN (SELECT * FROM USRAES.TMP_TITULARES_1 WHERE ESTADO_SUBS NOT LIKE 'D')  B
                ON (B.MSISDN=TO_CHAR(TO_NUMBER(A.ASNV_NUMERO_LINEA))) where b.asnv_direccion like '%POR SER PREPAGO%'"));
        //dd($data);

        $service = app(\AMovil\Shared\Exports\Domain\ExportService::class);
        $service->loadData(['asnv_direccion' => ['label' => 'A']], $data);
        
        $filename2 = storage_path('app/public')."/TEST.csv";
        $service->getWriter(WriterType::CSV)->save($filename2);
        return null;
    }
}
