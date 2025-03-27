<?php
namespace App\Http\Controllers\FacturacionFija;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Log\logReport;
use App\Exports\facturacionFija\FacturacionFijaExport;
use App\Exports\facturacionFija\FacturacionFijaSFExport;
use AMovil\Shared\Infrastructure\Eloquent\EloquentCriteriaConverter;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Carbon\Carbon;
use DateTime;

class FacturacionFijaController extends Controller {
    private $authService;
    private $userIdentifier;
    private $saveReportLog;

    public function __construct(AuthService $authService, SaveReportLog $saveReportLog)
    {
        $this->authService = $authService;
        $this->saveReportLog = $saveReportLog;
    }

    public function index(){
        $data = [
            'title' => 'Facturación Fija / Saliente'
        ];
        return view('backpack::facturacion_fija.saliente', compact('data'));
    }

    public function index_sf(){
        $data = [
            'title' => 'Facturación Fija / Saliente Sin Factura'
        ];
        return view('backpack::facturacion_fija.saliente_sf', compact('data'));
    }

    public function validReport(Request $request){
        $v_cod_clie = $request->get('cod_cliente');
        $v_fecha_ini = $request->get('f_ini');
        $v_fecha_fin = $request->get('f_fin');

        while(strlen($v_cod_clie)<8){
            $v_cod_clie = '0'.$v_cod_clie;
        }

        $result = DB::select(DB::RAW("SELECT COUNT(*) AS cliente FROM ( SELECT  distinct codcli FROM dws.SA_BILFAC WHERE codcli='$v_cod_clie')"));  

        if((int)$result[0]->cliente<1){
            return -1;
        }

        $result = DB::select(DB::RAW("SELECT COUNT(*) as cliente 
                                        FROM (
                                        SELECT CODCLI,trunc(horaini) 
                                        FROM DWS.SA_BILVALCDR 
                                        WHERE CODCLI='$v_cod_clie' AND trunc(horaini)>=to_date('$v_fecha_ini','dd/mm/yyyy') and trunc(horaini)<=to_date('$v_fecha_fin','dd/mm/yyyy') GROUP BY CODCLI,trunc(horaini)
                                        )"));

        if((int)$result[0]->cliente<1){
            return 0;
        }

        return 1;

    }

    public function generar_reporte(Request $request)
    {
        ini_set('max_execution_time', 1800); // Aumentar el tiempo de ejecución si es necesario
        ini_set('memory_limit', '2048M');

        $this->userIdentifier = $this->authService->getUserIdentifier();

        $v_cod_clie = $request->get('cod_cliente');
        $v_fecha_ini = $request->get('f_ini');
        $v_fecha_fin = $request->get('f_fin');

        // Validar si existe un registro con estado 0
        $procesoPendiente = DB::select("
            SELECT COUNT(*) AS total 
            FROM USRAES.TB_FIJA_FACTURADA_LOG 
            WHERE usuario = '{$this->userIdentifier}' AND (estado = 0 OR estado = 1)
        ")[0]->total;

        if ($procesoPendiente > 0) {
            // Si hay un proceso pendiente, regresar un mensaje a la vista
            return response()->json(['success' => false, 'message' => 'Ya existe un proceso en ejecución pendiente.']);
        }

        // Si no hay procesos pendientes, insertar un nuevo registro
        DB::statement("
            INSERT INTO USRAES.TB_FIJA_FACTURADA_LOG (usuario, estado, cod_cliente, fecha_ini, fecha_fin)
            VALUES (?, ?, ?, to_date(?, 'dd-mm-yyyy'), to_date(?, 'dd-mm-yyyy'))
        ", [
            $this->userIdentifier, 0, $v_cod_clie, $v_fecha_ini, $v_fecha_fin
        ]);

        // Formatear código cliente
        while (strlen($v_cod_clie) < 8) {
            $v_cod_clie = '0' . $v_cod_clie;
        }

        $plsql = [];
        // $plsql[] = "drop table USRAES.TB_FIJA_FACTURADA";
        $plsql[] = "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TB_FIJA_FACTURADA_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;";

        $plsql[] = "CREATE TABLE USRAES.TB_FIJA_FACTURADA_{$this->userIdentifier} as(
                select distinct a.codcli, a.numser serie_recibo, a.numsut num_recibo, ani telefono_origen, 
                dscisdest telefono_destino,dscserv servicio, b.nomdes nombre_destino,
                tipdest tipo_destino,
                TO_CHAR(horaini, 'DD/MM/YYYY HH24:MI:SS') horaini, 
                TO_CHAR(horafin, 'DD/MM/YYYY HH24:MI:SS') horafin,
                cantidad minutos,
                TRUNC((horafin - horaini) * (60 * 60 * 24)) + 1 segundos, idtiphor, 
                tarifa, monto, a.nomcli,b.idcon, b.idoperador, c.descripcion Operador,
                b.idclaisdest, d.descripcion Clase_Destino, b.idgrpdes, e.descripcion Grupo_Destino,cantidadval, 
                cantidadorigen
                from dws.SA_BILFAC a, DWS.SA_BILVALCDR b, DWS.SA_OPERADOR c, DWS.SA_CLASEISDESTINO d,
                DWS.SA_GRUPODESTINO e                
                where 
                a.idbilfac=b.idbilfac
                and c.idoperador=b.idoperador(+)
                and d.idclaisdest=b.idclaisdest(+)
                and e.idgrpdes=b.idgrpdes(+)
                and a.codcli='{$v_cod_clie}'
                and trunc(horaini)>=to_date('{$v_fecha_ini}','dd-mm-yyyy')
                and trunc(horaini)<=to_date('{$v_fecha_fin}','dd-mm-yyyy')
                )";

        foreach($plsql as $sql){
            DB::statement(DB::Raw($sql));
        }
        
        return response()->json(['success' => true, 'message' => 'Proceso iniciado correctamente, ir a descargar reportes.']);
    }

    public function generar_reporte_sf(Request $request){
        $fechaIniExec = new DateTime();
        $this->userIdentifier = $this->authService->getUserIdentifier();
        //$now = Carbon::now();
        //$v_log_id = (int) $now->format('YmdHis');
        $v_cod_clie = $request->get('cod_cliente');
        $v_fecha_ini = $request->get('f_ini');
        $v_fecha_fin = $request->get('f_fin');

        $reporteInput = ['cod_cliente' => $v_cod_clie, 'fechaInicio' => $v_fecha_ini, 'fechaFin' => $v_fecha_fin];

        while(strlen($v_cod_clie)<8){
            $v_cod_clie = '0'.$v_cod_clie;
        }     

        $resp = [];
        $plsql = [];
        // $plsql[] = "drop table USRAES.TB_FIJA_FACTURADA";
        $plsql[] = "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;";
        $plsql[] = "CREATE TABLE USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier} as(
                SELECT distinct codcli,
                    ani telefono_origen,
                    dscisdest telefono_destino,
                    dscserv servicio,
                    b.nomdes nombre_destino,
                    tipdest tipo_destino,
                    TO_CHAR(horaini, 'DD/MM/YYYY HH24:MI:SS') horaini,
                    TO_CHAR(horafin, 'DD/MM/YYYY HH24:MI:SS') horafin,
                    cantidad minutos,
                    TRUNC((horafin - horaini) * (60 * 60 * 24)) + 1 segundos,
                    idtiphor,
                    tarifa, monto, b.idcon, b.idoperador, c.descripcion Operador,
                    b.idclaisdest, d.descripcion Clase_Destino, b.idgrpdes, e.descripcion Grupo_Destino,cantidadval,
                    cantidadorigen
                from DWS.SA_BILVALCDR b, DWS.SA_OPERADOR c,
                    DWS.SA_CLASEISDESTINO d, DWS.SA_GRUPODESTINO e
                where c.idoperador=b.idoperador(+)
                    and d.idclaisdest=b.idclaisdest(+)
                    and e.idgrpdes=b.idgrpdes(+)
                    and CODCLI = '{$v_cod_clie}'
                    AND TO_CHAR(TRUNC(HORAINI),'YYYYMMDD') >= to_date('{$v_fecha_ini}','dd/mm/yyyy')
                    AND TO_CHAR(TRUNC(HORAINI),'YYYYMMDD') <= to_date('{$v_fecha_fin}','dd/mm/yyyy')
                )";

        try {
            $resp = [];
            foreach($plsql as $sql){
                $resp[] = DB::statement(DB::Raw($sql));
            }

            $result = DB::select(DB::RAW("select telefono_origen from USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier} where rownum = 1"));
            if(isset($result[0])){
                $tel_fijo = $result[0]->telefono_origen;
            }else{
                $tel_fijo = '';
            }

            $now = Carbon::now(); 

            /*logReport::insert([
                'hostname' => 'limnwkdaswfv01',
                'name' => 'FACTURACION FIJA',
                'direccion' => '',
                'area' => 'Control Regulatorio',
                'contacto' => \Auth::guard(backpack_guard_name())->user()->name,
                'responsable' => 'DIEGO MORENO',
                'file' => 'Facturacion_Fija_'.$tel_fijo.'_'.$now->format('Ymd').$now->format('H:i:s'),
                'ini' => $v_fecha_ini,
                'fin' => $v_fecha_fin,
                'lat' => 0,
                'estado' => 1,
                'mensaje' => 'se generó el archivo'
            ]);*/

            $filename = "FACTURACION_FIJA_".$fechaIniExec->format('YmdHis').".xlsx";
            $filePath = SaveReportLog::LOCAL_PATH."/MICHAELL_CIA_NN/{$filename}";

            Excel::store(new FacturacionFijaSFExport($this->authService), $filePath);

            $this->reportLog($filePath, $filename, $fechaIniExec, new DateTime(), $reporteInput, null);

            return Excel::download(new FacturacionFijaSFExport($this->authService), $filename);
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $fechaIniExec, new DateTime(), $reporteInput, $th);
            throw $th;
        }
    }

    public function reportes(){
        $config = [
            'title' => 'Descarga de reportes',
            'getApi' => url('facturacion-fija/reportes/search'),
            'downloadApi' => url('facturacion-fija/reportes/[file]/descargar'),
        ];
        return view('backpack::facturacion_fija.descargar_reportes', compact('config'));
    }

    public function search()
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $fields = [
            'fecha_creacion' => ["label" => 'Fecha Creación', "type" => 'datetime'],
            'usuario' => ["label" => 'Usuario', "type" => 'string'],
            'archivo' => ["label" => 'Archivo', "type" => 'string'],
            'estado' => ["label" => 'Estado', "type" => 'string']
        ];
        $filters = ["usuario.eq.".$this->userIdentifier];
        $builder = DB::table('USRAES.TB_FIJA_FACTURADA_LOG');

        EloquentCriteriaConverter::fromRawArray($builder, $fields, $filters, [], 0, 0);

        $response['data'] = $builder->get();
        return response()->json(["data" => $response['data']]);
    }

    public function descargar_reporte($file){
        $log = DB::table('USRAES.TB_FIJA_FACTURADA_LOG')
            ->where('usuario', $this->authService->getUserIdentifier())
            ->where('archivo', $file)
            ->first();

        if ($log === null) {
            return response()->json(['message' => "El reporte generado no existe"], 404);
        }
        $reporteInput = ['cod_cliente' => $log->cod_cliente, 'fechaInicio' => $log->fecha_ini, 'fechaFin' => $log->fecha_fin];
        $fechaIniExec = new DateTime();
        try {
            $rutaArchivo = storage_path('app/facturacion-fija/'.$file);
            $nombreArchivo = $file;
            
            $this->reportLog($rutaArchivo, $nombreArchivo, $fechaIniExec, new DateTime(), $reporteInput, null);

            return response()->download($rutaArchivo, $nombreArchivo);
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $fechaIniExec, new DateTime(), $reporteInput, $th);
            throw $th;
        }
    }

    public function test()
    {
        // SELECT * FROM SIGRE_TEST_LOG

        //$plsql = "BEGIN EXECUTE IMMEDIATE '".str_replace("'", "''", $plsql)."'; END;";
        //$plsql = "drop table tmp_final";
        //$data = DB::connection('oracle_dwo')->statement(DB::Raw($plsql));

        //$resp = $this->generar_reporte();
        
        //$data = DB::connection('oracle_dwo')->select('select * from usraes.prg_sigrei_log');
        //$data = DB::connection('oracle_dwo')->select('select * from usraes.prg_sigrei_final_log');
        //$data = DB::connection('oracle_dwo')->select('select a.* from SIGRE_TEST_LOG a right join dual on 1=1');
        $data = DB::connection('oracle')->table('reporte_sigrei_tmp')->get();
        return response()->json($data);
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?\Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'MICHAELL_CIA_NN',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'michaell-cia-nn',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        // if ($local_file !== null) {
        //     $this->saveReportLog->sendFileToRemoteServer($local_file, "MICHAELL_CIA_NN/{$filename}");
        // }
    }
}