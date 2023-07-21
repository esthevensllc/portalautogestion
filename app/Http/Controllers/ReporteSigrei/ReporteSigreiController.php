<?php
namespace App\Http\Controllers\ReporteSigrei;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReporteSigreiImport;
use App\Exports\ReporteSigreiExport;
use App\Models\ReportesSigrei\sigrei;
use DB;
use Carbon\Carbon;
use DateTime;

//use \Maatwebsite\Excel\Excel;


class ReporteSigreiController extends Controller {
    private $authService;
    private $userIdentifier;
    private $exportService;
    private $saveReportLog;
    
    public function __construct(AuthService $authService, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->authService = $authService;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function index(){
        return view('backpack::reporte_sigrei');
    }

    public function import(Request $request) 
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $file = $request->file('fileImport');

        DB::statement(DB::raw("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE reporte_sigrei_tmp_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN
                RAISE;
            END IF;
        END;"));
        DB::statement(DB::raw("CREATE TABLE reporte_sigrei_tmp_{$this->userIdentifier}(
        IMEI NUMBER not null,
        ESTADO_DEL_REPORTE VARCHAR2(50),
        FECHA_REPORTE VARCHAR2(100),
        CONTADOR NUMBER
        )"));
        Excel::import(new ReporteSigreiImport($this->userIdentifier), $file);

        //DB::connection('mysql')->select('call sp_reporte_sigrei_web()');

        return back()->with('message', 'Importación completada');
    }

    public function export() 
    {
        ini_set('max_execution_time', '300');
        set_time_limit(300);
        $dt_start = new DateTime();
        try {
            $this->userIdentifier = $this->authService->getUserIdentifier();

            /*try {
                DB::connection('oracle_dwo')->statement(DB::Raw($plsql));
            } catch (\Throwable $th) {
                //throw $th;
                throw new \Exception('Ocurrió un error al generar el archivo: '.$th->getMessage());
            }*/
            $this->generar_reporte();

            $data = collect(DB::select(DB::RAW("select contador,imei, tipo, to_char(fecha, 'dd/mm/yyyy hh24:mi:ss') as fecha, numero_servicio_telefonico, apellido_paterno, apellido_materno, nombres, tipo_doc, nro_documento, razon_social from TMP_FINAL_2_{$this->userIdentifier}")));

            $headers = [
                "contador" => ["label" => 'Nro.'],
                "imei" => ["label" => 'IMEI'],
                "tipo" => ["label" => 'ESTADO DEL REPORTE'],
                "fecha" => ["label" => 'FECHA DEL REPORTE 
                (dd/mm/aaaa  hh:mi:ss)'],
                "numero_servicio_telefonico" => ["label" => 'NÚMERO DE SERVICIO'],
                "apellido_paterno" => ["label" => 'APELLIDO PATERNO DEL POSIBLE TITULAR DEL EQUIPO'],
                "apellido_materno" => ["label" => 'APELLIDO MATERNO DEL POSIBLE TITULAR DEL EQUIPO'],
                "nombres" => ["label" => 'NOMBRES DEL POSIBLE TITULAR DEL EQUIPO'],
                "tipo_doc" => ["label" => 'TIPO DE DOCUMENTO LEGAL
                1=DNI,
                2=RUC,
                3=Carné de Extranjería,
                4=Pasaporte,
                5=Documento Legal de Identidad válido requerido por la SNM.'],
                "nro_documento" => ["label" => "NÚMERO DE DOCUMENTO LEGAL"],
                "razon_social" => ["label" => 'RAZON SOCIAL']
            ];
            $this->exportService->loadData($headers, $data, []);

            $this->reportLog($this->exportService, $dt_start, new DateTime(), ["filename" => "SIGREI_".$dt_start->format("YmdHis").".csv"]);

            return Excel::download(new ReporteSigreiExport($this->userIdentifier), 'reporte_sigrei.xlsx');   
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime(), ["mensaje" =>  $th->getMessage()]);
            throw $th;
        }

        /*return (new ReporteSigreiExport)->download('reporte_sigrei.csv', \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);*/
    }

    /* Genera reporte sigrei */
    public function generar_reporte(){
        $now = Carbon::now();
        $v_log_id = (int) $now->format('YmdHis');
        $v_date = $now->format('Y-m-d H:i:s');
        $v_user = null;
        $v_ip_address = $_SERVER['REMOTE_ADDR'];

        $resp = [];
        $plsql = [];
        $plsql[] = "BEGIN
            INSERT INTO usraes.prg_sigrei_log(imei,estado_del_reporte,fecha_reporte,log_id,log_date,log_user,log_ip_address)
            select imei,estado_del_reporte,fecha_reporte, {$v_log_id}, to_date('{$v_date}', 'YYYY-MM-DD HH24:MI:SS'), null, '{$v_ip_address}' from reporte_sigrei_tmp_{$this->userIdentifier};
            commit;

            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE TMP_IMEI_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;
            EXECUTE IMMEDIATE 'create table tmp_imei_{$this->userIdentifier} (contador number,imei varchar2(50),tipo varchar2(50),fecha DATE)';

            EXECUTE IMMEDIATE 'INSERT INTO tmp_imei_{$this->userIdentifier}(contador,IMEI, tipo, fecha)
            select contador,LPAD(to_char(imei), 15, ''0'') as imei,estado_del_reporte,to_date(fecha_reporte, ''dd/mm/yyyy hh24:mi:ss'') from reporte_sigrei_tmp_{$this->userIdentifier}';
            commit;

            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE tmp_imei_1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;
            EXECUTE IMMEDIATE 'create table tmp_imei_1_{$this->userIdentifier} as
            select a.contador,a.imei,a.tipo,a.fecha,''51''||b.ter_numerolinea msisdn,b.Ter_estado,b.ter_des_motivo from tmp_imei_{$this->userIdentifier} A LEFT JOIN
            dm.ter_claro_movimiento B
            ON A.IMEI=B.NUMERO_IMEI
            and to_char(a.fecha,''dd/mm/yyyy'')=to_char(b.ter_fecregistro,''dd/mm/yyyy'') AND TER_ESTADO=''BLOQUEADO''';
            COMMIT;

            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE tmp_final_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;
            EXECUTE IMMEDIATE 'create table tmp_final_{$this->userIdentifier} as
            select A.*,B.MES,B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO,B.NOMBRES,B.APELLIDOS from tmp_imei_1_{$this->userIdentifier} A
            LEFT JOIN  DM.F_M_ABONADOS B ON A.MSISDN=B.MSISDN AND B.MES=TO_CHAR(FECHA,''YYYYMM'')';
            COMMIT;

            EXECUTE IMMEDIATE 'merge into tmp_final_{$this->userIdentifier} a
            using (SELECT DISTINCT A.MSISDN,A.TER_ESTADO,A.TER_DES_MOTIVO,B.MES,B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO,B.NOMBRES,B.APELLIDOS
            FROM tmp_final_{$this->userIdentifier} A INNER JOIN dm.f_M_abonados B ON A.MSISDN=B.MSISDN WHERE TO_CHAR(add_months(FECHA,-1),''YYYYMM'')=B.MES AND  A.NOMBRES IS NULL ) b
            on (a.MSISDN=b.MSISDN)
            when matched then
            update set a.MES=B.MES,
                a.TIPO_DOCUMENTO= b.TIPO_DOCUMENTO,
                A.NRO_DOCUMENTO=B.NRO_DOCUMENTO,
                A.NOMBRES=B.NOMBRES,
                A.APELLIDOS=B.APELLIDOS
                where A.NOMBRES IS NULL';
            COMMIT;

        END;";


        $plsql[] = "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN
                RAISE;
            END IF;
        END;";
        $plsql[] = "CREATE TABLE USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier}(
            MSISDN VARCHAR2(20),
            MES CHAR(6),
            TIPO_DOCUMENTO VARCHAR2(500),
            NRO_DOCUMENTO VARCHAR2(50),
            NOMBRES VARCHAR2(200),
            APELLIDOS VARCHAR2(100)
        )";
        $plsql[] = "DECLARE
            V_COUNTER NUMBER;
            CURSOR CUR_NOMBRES_NULL IS
            SELECT TO_CHAR(FECHA,'YYYYMM') AS MES, MSISDN, TO_CHAR(FECHA, 'DD/MM/YYYY') AS FECHA FROM tmp_final_{$this->userIdentifier} WHERE NOMBRES IS NULL;
        BEGIN
            FOR V_ROW IN CUR_NOMBRES_NULL
            LOOP
                SELECT COUNT(*) INTO V_COUNTER FROM USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier} WHERE MSISDN = V_ROW.MSISDN;
                IF V_COUNTER = 0 THEN
                    BEGIN
                        EXECUTE IMMEDIATE 'INSERT INTO USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier}(MSISDN,MES,TIPO_DOCUMENTO,NRO_DOCUMENTO,NOMBRES,APELLIDOS)
                        SELECT
                        s.subscription_access_number MSISDN,
                        S.PERIOD MES,
                        S.ID_CARD_TYPE_VALUE TIPO_DOCUMENTO,
                        S.ID_CARD_VALUE NRO_DOCUMENTO,
                        S.CUSTOMER_FIRST_NAME NOMBRES,
                        S.CUSTOMER_LAST_NAME APELLIDOS
                        FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION (P_'||TO_CHAR(SYSDATE - INTERVAL '2' DAY, 'YYYYMM')||') S
                        where 
                        s.subscription_access_number in ('''|| V_ROW.MSISDN ||''')
                        AND TRUNC(SUBSCRIPTION_END_DATE, ''DD'') >= to_Date('''|| V_ROW.FECHA ||''',''dd/mm/yyyy'') 
                        AND to_Date('''|| V_ROW.FECHA ||''', ''dd/mm/yyyy'') >= TRUNC(SUBSCRIPTION_START_DATE, ''DD'')
                        ';
                    EXCEPTION
                        WHEN OTHERS THEN
                            V_COUNTER := 0;
                    END;
                END IF;
                
                COMMIT;
        
            END LOOP;

            DECLARE
                CURSOR CUR_NOMBRES_NULL2 IS
                SELECT TO_CHAR(A.FECHA,'YYYYMM') AS MES, A.MSISDN, A.IMEI, TO_CHAR(A.FECHA, 'DD/MM/YYYY') AS FECHA FROM tmp_final_{$this->userIdentifier} A
                LEFT JOIN USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier} B ON A.MSISDN = B.MSISDN
                WHERE B.NOMBRES IS NULL AND A.NOMBRES IS NULL;
            BEGIN
                FOR V_ROW2 IN CUR_NOMBRES_NULL2
                LOOP
                    SELECT COUNT(*) INTO V_COUNTER FROM USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier} WHERE MSISDN = V_ROW2.MSISDN;
                    IF V_COUNTER = 0 THEN
                        BEGIN
                            INSERT INTO USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier}(MSISDN,MES,TIPO_DOCUMENTO,NRO_DOCUMENTO,NOMBRES,APELLIDOS)
                            select
                            V_ROW2.MSISDN MSISDN,
                            to_char(sale_date,'yyyymm') MES,
                            customer_document_type_desc TIPO_DOCUMENTO,
                            id_card_value NRO_DOCUMENTO,
                            CUSTOMER_GIVEN_NAME NOMBRES,
                            CUSTOMER_FATHER_LASTNAME||' '||CUSTOMER_MOTHER_LASTNAME APELLIDOS
                            from dwa.dw_t_customer_sale a where 
                            product_number like '%'||V_ROW2.MSISDN and
                            serial_number like '%'||V_ROW2.IMEI and
                            to_Date(V_ROW2.FECHA,'DD/MM/YYYY') >= SALE_DATE and
                            customer_document_type_desc is not null and
                            id_card_value is not null and
                            CUSTOMER_GIVEN_NAME is not null and
                            CUSTOMER_FATHER_LASTNAME is not null;
                        EXCEPTION
                            WHEN OTHERS THEN
                                V_COUNTER := 0;
                        END;
                    END IF;
                    
                    COMMIT;
            
                END LOOP;
            END;
        
            MERGE INTO tmp_final_{$this->userIdentifier} a
            USING (
            --SELECT * FROM USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier}
            SELECT MSISDN, min(MES) MES, min(TIPO_DOCUMENTO) TIPO_DOCUMENTO,
            min(NRO_DOCUMENTO) NRO_DOCUMENTO, min(NOMBRES) NOMBRES, min(APELLIDOS) APELLIDOS
            FROM USRAES.TMP_NOMBRES_NOT_NULL_{$this->userIdentifier}
            group by MSISDN
            ) b
            on (a.MSISDN = b.MSISDN)
            when matched then
            update set
            a.MES = B.MES,
            a.TIPO_DOCUMENTO = b.TIPO_DOCUMENTO,
            A.NRO_DOCUMENTO = B.NRO_DOCUMENTO,
            A.NOMBRES = B.NOMBRES,
            A.APELLIDOS = B.APELLIDOS
            where A.NOMBRES IS NULL;
            COMMIT;

            UPDATE tmp_final_{$this->userIdentifier} SET
            TIPO_DOCUMENTO = TRIM(TIPO_DOCUMENTO);
            COMMIT;
        
        END;";


        $plsql[] = "ALTER TABLE tmp_final_{$this->userIdentifier} ADD (RAZON_SOCIAL VARCHAR2(200))";
        $plsql[] = "BEGIN
        merge into tmp_final_{$this->userIdentifier} a
        using ( SELECT * FROM(  SELECT T.*,ROW_NUMBER () OVER(PARTITION BY T.NRO_DOCUMENTO ORDER BY T.CUSTOMER_ID DESC) AS FLAG FROM (
        SELECT DISTINCT A.*,B.CCNAME, B.CUSTOMER_ID
        FROM tmp_final_{$this->userIdentifier} A INNER JOIN DWS.SA_CCONTACT_ALL B ON B.CSCOMPREGNO=A.NRO_DOCUMENTO WHERE  A.TIPO_DOCUMENTO='RUC')T
        )
        WHERE FLAG='1'
        ) b
        on (a.NRO_DOCUMENTO=b.NRO_DOCUMENTO)
        when matched then
        update set A.RAZON_SOCIAL=B.CCNAME
            where A.TIPO_DOCUMENTO ='RUC';
        COMMIT;
        END;";

        $plsql[] = "BEGIN
            MERGE INTO usraes.tmp_final_{$this->userIdentifier} A
            USING (
                SELECT NRO_DOCUMENTO,RAZON_SOCIAL,NOMBRES,APELLIDOS FROM (
                SELECT CSCOMPREGNO NRO_DOCUMENTO,CCNAME RAZON_SOCIAL,CCFNAME NOMBRES,CCLNAME APELLIDOS,ROW_NUMBER() OVER(PARTITION BY CSCOMPREGNO ORDER BY CUSTOMER_ID DESC ) FLAG FROM DWS.SA_CCONTACT_ALL WHERE CSCOMPREGNO IN (
                SELECT NRO_DOCUMENTO FROM USRAES.tmp_final_{$this->userIdentifier} WHERE NOMBRES IS NULL AND TIPO_DOCUMENTO='RUC')
                ) WHERE FLAG=1
            ) B
            on (a.NRO_DOCUMENTO=b.NRO_DOCUMENTO AND A.RAZON_SOCIAL = B.RAZON_SOCIAL)
            when matched then
            update set
            A.NOMBRES=B.NOMBRES,
            A.APELLIDOS=B.APELLIDOS;
            COMMIT;
        END;";

        $plsql[] = "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE tmp_final_1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;

            EXECUTE IMMEDIATE 'create table tmp_final_1_{$this->userIdentifier} as
            select A.CONTADOR,A.IMEI,A.TIPO,A.FECHA,TO_NUMBER(substr(A.MSISDN,3,12)) NUMERO_SERVICIO_TELEFONICO,A.TIPO_DOCUMENTO,
            CASE WHEN TIPO_DOCUMENTO =''DNI'' OR TIPO_DOCUMENTO=''CIP'' THEN ''01''
                WHEN  TIPO_DOCUMENTO =''RUC'' THEN ''02''
                WHEN  TIPO_DOCUMENTO =''C.E.'' OR TIPO_DOCUMENTO=''CDE'' OR TIPO_DOCUMENTO=''Carnet Extranjería'' THEN ''03''
                WHEN TIPO_DOCUMENTO =''CPP'' THEN ''05''
                WHEN  UPPER(TIPO_DOCUMENTO) =''PASAPORTE'' THEN ''04'' END TIPO_DOC,LTRIM(A.NRO_DOCUMENTO) NRO_DOCUMENTO,
                    TRIM(A.NOMBRES) NOMBRES,TRIM(SUBSTR(TRIM(A.APELLIDOS),0,INSTR(TRIM(A.APELLIDOS),'' ''))) APELLIDO_PATERNO,
            TRIM(SUBSTR(TRIM(A.APELLIDOS),INSTR(TRIM(A.APELLIDOS),'' ''))) APELLIDO_MATERNO ,  A.RAZON_SOCIAL
            from tmp_final_{$this->userIdentifier} A';
            COMMIT;

            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE TMP_FINAL_2_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;

            EXECUTE IMMEDIATE 'CREATE TABLE TMP_FINAL_2_{$this->userIdentifier} AS
            SELECT CONTADOR,IMEI,TIPO,FECHA,NUMERO_SERVICIO_TELEFONICO,
            CASE WHEN APELLIDO_PATERNO IS NULL AND APELLIDO_MATERNO IS NULL THEN ''.''
                WHEN APELLIDO_PATERNO IS NULL AND APELLIDO_MATERNO IS NOT NULL THEN APELLIDO_MATERNO
                 WHEN  APELLIDO_PATERNO IS NOT NULL THEN APELLIDO_PATERNO
              END APELLIDO_PATERNO,
            CASE WHEN APELLIDO_MATERNO IS NULL THEN ''.''
              WHEN  APELLIDO_MATERNO IS NOT NULL THEN APELLIDO_MATERNO
              END APELLIDO_MATERNO,NOMBRES,TIPO_DOC TIPO_DOC,NRO_DOCUMENTO,RAZON_SOCIAL
            FROM tmp_final_1_{$this->userIdentifier}';
            COMMIT;

            
            COMMIT;
        END;";

        $resp = [];
        foreach($plsql as $sql){
            $resp[] = DB::statement(DB::Raw($sql));
        }
        return $resp;
    }

    /* Guarda log del reporte sigrei */
    private function reportLog(?ExportService $exportService, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "SIGREI_".$ini->format('YmdHis');
        $data = array_merge([
            'name' => 'SIGREI',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => "sigrei",
        ], $extra_data);
        $this->saveReportLog->fromExport($exportService, $data, $filename, 'SIGREI');
    }
}