<?php

namespace AMovil\Reports\Mtc\Suspensiones\Services;

use AMovil\Reports\Mtc\Suspensiones\Domain\MtcSuspensionesRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class ExportReporteSuspensiones
{
    private $repo;
    private $exportService;
    private $storage_path;
    private $saveReportLog;
    private $dt_start;

    public function __construct(MtcSuspensionesRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->storage_path = storage_path('app/public');
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($tipo_reporte, $txtFile): Response
    {
        $this->dt_start = new DateTime();
        try {
            $data = $this->getSuspensionesFromTxt($txtFile['all_filename']);
            $response = null;

            $filename_parts = explode("_", $txtFile['filename']);
            $str_fecha = str_replace(".txt", "", $filename_parts[count($filename_parts)-1]);
            $periodo = DateTime::createFromFormat("Ymd", $str_fecha);
            if($periodo->format('d') === '01'){
                $periodo->modify("-1 day");
            }

            switch ($tipo_reporte) {
                case '1':
                    $this->repo->generateReporteSuspensiones($periodo, $data);
                    $response = $this->getReporteSuspensiones($txtFile);
                    break;
                case '2':
                    $this->repo->generateReporteTitularidad($periodo, $data);
                    $response = $this->getReporteTitularidad($txtFile);
                    break;
                case '3':
                    $this->repo->generateReporteMensual($periodo, $data);
                    $response = $this->getReporteMensual($txtFile);
                    break;
                case '4':
                    $this->repo->generateReporteNotificacion($periodo, $data);
                    $response = $this->getReporteNotificaciones($txtFile);
                    break;
                default:
                    # code...
                    break;
            }

            $validate_message = null;
            if(session('reporte_mtc') !== null){
                $mtc_reporte_log = json_decode(session('reporte_mtc'));
                if((int) $mtc_reporte_log->expires_at > (new DateTime())->getTimestamp()){
                    if($mtc_reporte_log->tipo_reporte === '1' && $tipo_reporte === '2'){
                        $suspensiones = $this->repo->getCountSuspensionesByAsncEstado('S');
                        $titularidad = $this->repo->getCountRepTitularidadByAsncTipoTitular('R');
                        if($suspensiones !== $titularidad){
                            $validate_message = "Reporte de titularidad: {$titularidad}, Reporte suspensiones: {$suspensiones}";
                            $response['message'] = $validate_message;
                        }
                    }
                }
            }

            if($validate_message === null){
                //$response['message'] = "test message";
            }

            $expires_at = new DateTime();
            $expires_at->modify("+30 minute");

            if($tipo_reporte !== '2'){
                session(['reporte_mtc' => json_encode([
                    'tipo_reporte' => $tipo_reporte,
                    'expires_at' => $expires_at->getTimestamp()
                ])]);
            }

            return new Response([], $response);
        } catch (\Throwable $th) {
            $this->reportLog(null, $this->dt_start, new DateTime(), null, ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function getReporteSuspensiones($txtFile)
    {
        $filename_parts = explode("_", $txtFile['filename']);
        $str_fecha = str_replace(".txt", "", $filename_parts[count($filename_parts)-1]);
        $dt = DateTime::createFromFormat("Ymd", $str_fecha);
        $dt2 = (clone $dt)->modify('-1 day');

        $plantilla = $this->repo->getPlantilla();

        //$numberFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_TEXT]];

        $headers = [
            'asnv_identificadorgrupomtc' => ['label' => 'ASNV_IDENTIFICADORGRUPOMTC'],
            'asnn_identificador' => ['label' => 'ASNN_IDENTIFICADOR'],
            'asnc_tipo_reporte' => ['label' => 'ASNC_TIPO_REPORTE'],
            'asnd_fecha_reporte' => ['label' => 'ASND_FECHA_REPORTE'],
            'asnc_tipo_telefonico' => ['label' => 'ASNC_TIPO_TELEFONICO'],
            'asnv_numero_linea' => ['label' => 'ASNV_NUMERO_LINEA'],
            'asnc_tipo_suspencion' => ['label' => 'ASNC_TIPO_SUSPENCION'],
            'asnn_num_dias_suspende' => ['label' => 'ASNN_NUM_DIAS_SUSPENDE'],
            'asnv_mensaje_mtc' => ['label' => 'ASNV_MENSAJE_MTC'],
            'asnc_estado' => ['label' => 'ASNC_ESTADO'],
            'asnd_fecha_ini_suspension' => ['label' => 'ASND_FECHA_INI_SUSPENSION'],
            'asnd_fecha_fin_suspension' => ['label' => 'ASND_FECHA_FIN_SUSPENSION'],
            'asnc_flag_reactivado' => ['label' => 'ASNC_FLAG_REACTIVADO'],
            'asnd_fecha_reactivacion' => ['label' => 'ASND_FECHA_REACTIVACION'],
            'asnc_motivo_nosuspension' => ['label' => 'ASNC_MOTIVO_NOSUSPENSION'],
            'asnd_fecha_baja' => ['label' => 'ASND_FECHA_BAJA'],
            'asnc_tipo_titular' => ['label' => 'ASNC_TIPO_TITULAR'],
            'asnc_tipo_contrato' => ['label' => 'ASNC_TIPO_CONTRATO'],
            'asnc_tipo_doctitular' => ['label' => 'ASNC_TIPO_DOCTITULAR'],
            'asnv_num_doctitular' => ['label' => 'ASNV_NUM_DOCTITULAR'],
            'asnv_nombretitular' => ['label' => 'ASNV_NOMBRETITULAR'],
            'asnd_fec_inicontrato' => ['label' => 'ASND_FEC_INICONTRATO'],
            'asnd_fec_fincontrato' => ['label' => 'ASND_FEC_FINCONTRATO'],
            'asnv_direccion' => ['label' => 'ASNV_DIRECCION'],
            'asnv_ubigeo' => ['label' => 'ASNV_UBIGEO'],
        ];

        $this->exportService->loadData($headers, $plantilla);

        $filename1 = "{$this->storage_path}/".Uuid::uuid4()->toString().".csv";
        $this->exportService->getWriter(WriterType::CSV)->save($filename1);

        $lineas = $this->repo->getLineas();
        $filename2 = "{$this->storage_path}".Uuid::uuid4()->toString().".xlsx";
        $this->exportService->reset();
        $this->exportService->loadData([
            'lineas' => ['label' => 'LINEAS'],
            'modalidad' => ['label' => 'MODALIDAD'],
        ], $lineas, [
            'title' => $dt2->format('Ymd'),
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                ],
                'body' => ['font' => ['size' => 9]]
            ]
        ]);
        $this->exportService->getWriter(WriterType::XLSX)->save($filename2);

        $lineas_moviles = $this->repo->getLineasMoviles();
        $filename3 = "{$this->storage_path}/".Uuid::uuid4()->toString()."txt";
        $fp = fopen($filename3, 'w');
	    fwrite($fp, "MSISDN,MENSAJE\n");
        foreach($lineas_moviles as $row){
            fwrite($fp, $row->mensaje."\n");
        }
        fclose($fp);

        $lineas_fijas = $this->repo->getLineasFijas();
        $filename4 = "{$this->storage_path}/".Uuid::uuid4()->toString()."txt";
        $fp = fopen($filename4, 'w');
        fwrite($fp, "ACCEPTPHONE\n");
        foreach($lineas_fijas as $row){
            fwrite($fp, $row->acceptphone."\n");
        }
        fclose($fp);

        $zip = new ZipArchive();
        $str_time = (new DateTime())->format("YmdHis");
        $zipFilename = "{$this->storage_path}/REPORTE_SUSPENSIONES_{$str_time}.zip";
        $zip->open($zipFilename, ZipArchive::CREATE);

        

        /*$zip->addFile($filename1, "MTC_E_SUS_{$str_fecha}.csv");
        $zip->addFile($filename2, "LINEAS_{$str_fecha}.xlsx");
        $zip->addFile($filename3, "Movil_SMS_".$dt->format('dmY').".txt");
        $zip->addFile($filename4, "Fija_IVR_".$dt->format('d-m-Y').".txt");*/
        $csv_content = utf8_decode(file_get_contents($filename1));
        $zip->addFromString("MTC_E_SUS_{$str_fecha}.csv", str_replace('"', '', $csv_content));
        $zip->addFromString("LINEAS_{$str_fecha}.xlsx", file_get_contents($filename2));
        $zip->addFromString("SMS_REGU_MT_MREG_LLA_MAL_".$dt->format('dmY').".txt", file_get_contents($filename3));
        $zip->addFromString("Fija_IVR_".$dt->format('d-m-Y').".txt", file_get_contents($filename4));
        //$zip->addFromString('test.txt', 'ñ');
        $zip->close();

        $export_content = file_get_contents($zipFilename);
        $this->reportLog($zipFilename, $this->dt_start, new DateTime(), "SUSPENSIONES_".$this->dt_start->format('YmdHis').".zip");

        unlink($zipFilename);
        unlink($filename1);
        unlink($filename2);
        unlink($filename3);
        unlink($filename4);

        return [
            'content' => $export_content,
            'type' => 'zip',
            'filename' => "REPORTE_SUSPENSIONES_{$str_time}.zip"
        ];
    }

    private function getReporteTitularidad($txtFile){
        $filename_parts = explode("_", $txtFile['filename']);
        $str_fecha = str_replace(".txt", "", $filename_parts[count($filename_parts)-1]);
        //$dt = DateTime::createFromFormat("Ymd", $str_fecha);

        $headers = [
            "asnv_identificadorgrupomtc" => ["label" => "ASNV_IDENTIFICADORGRUPOMTC"],
            "asnn_identificador" => ["label" => "ASNN_IDENTIFICADOR"],
            "asnc_tipo_reporte" => ["label" => "ASNC_TIPO_REPORTE"],
            "asnd_fecha_reporte" => ["label" => "ASND_FECHA_REPORTE"],
            "asnc_tipo_telefonico" => ["label" => "ASNC_TIPO_TELEFONICO"],
            "asnv_numero_linea" => ["label" => "ASNV_NUMERO_LINEA"],
            "asnc_tipo_titular" => ["label" => "ASNC_TIPO_TITULAR"],
            "asnc_tipo_contrato" => ["label" => "ASNC_TIPO_CONTRATO"],
            "asnc_tipo_doctitular" => ["label" => "ASNC_TIPO_DOCTITULAR"],
            "asnv_num_doctitular" => ["label" => "ASNV_NUM_DOCTITULAR"],
            "asnv_nombretitular" => ["label" => "ASNV_NOMBRETITULAR"],
            "asnd_fec_inicontrato" => ["label" => "ASND_FEC_INICONTRATO"],
            "asnd_fec_fincontrato" => ["label" => "ASND_FEC_FINCONTRATO"],
            "asnv_direccion" => ["label" => "ASNV_DIRECCION"],
            "asnv_ubigeo" => ["label" => "ASNV_UBIGEO"],
            "asnd_fechahora_llamada" => ["label" => "ASND_FECHAHORA_LLAMADA"],
        ];
        $data = $this->repo->getReporteTitularidad();

        $this->exportService->loadData($headers, $data);
        
        $filename1 = "{$this->storage_path}/".Uuid::uuid4()->toString()."_temp.csv";
        $this->exportService->getWriter(WriterType::CSV)->save($filename1);
        $export_content = str_replace('"', '', file_get_contents($filename1));

        $this->reportLog($filename1, $this->dt_start, new DateTime(), "TITULARIDAD_".$this->dt_start->format('YmdHis').".csv");
        unlink($filename1);

        return [
            'content' => $export_content,
            'type' => 'csv',
            'filename' => "MTC_E_00_{$str_fecha}.csv"
        ];
    }

    private function getReporteMensual($txtFile){
        $filename_parts = explode("_", $txtFile['filename']);
        $str_fecha = str_replace(".txt", "", $filename_parts[count($filename_parts)-1]);

        $headers = [
            "asnv_identificadorgrupomtc" => ["label" => "ASNV_IDENTIFICADORGRUPOMTC"],
            "asnn_identificador" => ["label" => "ASNN_IDENTIFICADOR"],
            "asnc_tipo_reporte" => ["label" => "ASNC_TIPO_REPORTE"],
            "asnd_fecha_reporte" => ["label" => "ASND_FECHA_REPORTE"],
            "asnc_tipo_telefonico" => ["label" => "ASNC_TIPO_TELEFONICO"],
            "asnv_numero_linea" => ["label" => "ASNV_NUMERO_LINEA"],
            "asnc_tipo_titular" => ["label" => "ASNC_TIPO_TITULAR"],
            "asnc_tipo_contrato" => ["label" => "ASNC_TIPO_CONTRATO"],
            "asnc_tipo_doctitular" => ["label" => "ASNC_TIPO_DOCTITULAR"],
            "asnv_num_doctitular" => ["label" => "ASNV_NUM_DOCTITULAR"],
            "asnv_nombretitular" => ["label" => "ASNV_NOMBRETITULAR"],
            "asnd_fec_inicontrato" => ["label" => "ASND_FEC_INICONTRATO"],
            "asnd_fec_fincontrato" => ["label" => "ASND_FEC_FINCONTRATO"],
            "asnv_direccion" => ["label" => "ASNV_DIRECCION"],
            "asnv_ubigeo" => ["label" => "ASNV_UBIGEO"],
            //"asnd_fechahora_llamada" => ["label" => "ASND_FECHAHORA_LLAMADA"],
        ];

        $data = $this->repo->getReporteMensual();

        $this->exportService->loadData($headers, $data);
        
        $filename = "{$this->storage_path}/".Uuid::uuid4()->toString().".csv";
        $this->exportService->getWriter(WriterType::CSV)->save($filename);
        $export_content = str_replace('"', '', file_get_contents($filename));
        
        $this->reportLog($filename, $this->dt_start, new DateTime(), "MENSUAL_".$this->dt_start->format('YmdHis').".csv");
        unlink($filename);
        
        return [
            'content' => $export_content,
            'type' => 'csv',
            'filename' => "MTC_E_REP_{$str_fecha}.csv"
        ];
    }

    private function getReporteNotificaciones($txtFile){
        $filename_parts = explode("_", $txtFile['filename']);
        $str_fecha = str_replace(".txt", "", $filename_parts[count($filename_parts)-1]);

        $headers = [
            "asnv_identificadorgrupomtc" => ["label" => "ASNV_IDENTIFICADORGRUPOMTC"],
            "asnn_identificador" => ["label" => "ASNN_IDENTIFICADOR"],
            "asnc_tipo_reporte" => ["label" => "ASNC_TIPO_REPORTE"],
            "asnd_fecha_reporte" => ["label" => "ASND_FECHA_REPORTE"],
            "asnc_tipo_telefonico" => ["label" => "ASNC_TIPO_TELEFONICO"],
            "asnv_numero_linea" => ["label" => "ASNV_NUMERO_LINEA"],
            "asnv_mensaje_mtc" => ["label" => "ASNV_MENSAJE_MTC"],
            "asnc_estado" => ["label" => "ASNC_ESTADO"],
            "asnd_fecha_notificacion" => ["label" => "ASND_FECHA_NOTIFICACION"],
            "asnc_motivo_nonotifica" => ["label" => "ASNC_MOTIVO_NONOTIFICA"],
            "asnc_tipo_titular" => ["label" => "ASNC_TIPO_TITULAR"],
            "asnc_tipo_contrato" => ["label" => "ASNC_TIPO_CONTRATO"],
            "asnc_tipo_doctitular" => ["label" => "ASNC_TIPO_DOCTITULAR"],
            "asnv_num_doctitular" => ["label" => "ASNV_NUM_DOCTITULAR"],
            "asnv_nombretitular" => ["label" => "ASNV_NOMBRETITULAR"],
            "asnd_fec_inicontrato" => ["label" => "ASND_FEC_INICONTRATO"],
            "asnd_fec_fincontrato" => ["label" => "ASND_FEC_FINCONTRATO"],
            "asnv_direccion" => ["label" => "ASNV_DIRECCION"],
            "asnv_ubigeo" => ["label" => "ASNV_UBIGEO"],
        ];

        $data = $this->repo->getReporteNotificacion();
        $this->exportService->loadData($headers, $data);
        
        $filename1 = "{$this->storage_path}/".Uuid::uuid4()->toString().".csv";
        $this->exportService->getWriter(WriterType::CSV)->save($filename1);
        $export_content = str_replace('"', '', file_get_contents($filename1));

        $this->reportLog($filename1, $this->dt_start, new DateTime(), "NOTIFICACIONES_".$this->dt_start->format('YmdHis').".csv");
        unlink($filename1);

        return [
            'content' => $export_content,
            'type' => 'csv',
            'filename' => "MTC_E_ALE_{$str_fecha}.csv"
        ];
    }

    private function getSuspensionesFromTxt($txtFile): array
    {
        $file = fopen($txtFile, 'r');
        $data = [];
        while (!feof($file)) {
            $line = fgets($file);
            if(strlen($line) > 0){
                $data[] = explode(";", $line);
            }
        }
        fclose($file);
        return $data;
    }

    private function reportLog($local_file, DateTime $ini, DateTime $fin, $filename, array $extra_data = [])
    {
        $this->saveReportLog->__invoke(
            [
                'name' => 'MTC',
                'ini' => $ini->format('Y-m-d H:i:s'),
                'fin' => $fin->format('Y-m-d H:i:s'),
                'filename' => $filename,
                'trac_name' => 'mtc.suspensiones',
                'mensaje' => $extra_data['mensaje'] ?? null
            ],
            $local_file,
            'MTC_SUSPENSIONES'
        );
    }
}
