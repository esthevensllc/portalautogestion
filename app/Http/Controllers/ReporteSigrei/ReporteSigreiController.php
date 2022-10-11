<?php
namespace App\Http\Controllers\ReporteSigrei;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReporteSigreiImport;
use App\Exports\ReporteSigreiExport;
use App\Models\ReportesSigrei\sigrei;
use DB;
use Carbon\Carbon;
//use \Maatwebsite\Excel\Excel;


class ReporteSigreiController extends Controller {
    public function index(){
        return view('backpack::reporte_sigrei');
    }

    public function import(Request $request) 
    {

        $file = $request->file('fileImport');

        sigrei::truncate();
        Excel::import(new ReporteSigreiImport, $file);

        //DB::connection('mysql')->select('call sp_reporte_sigrei_web()');

        return back()->with('message', 'Importación completada');
    }

    public function export() 
    {
        ini_set('max_execution_time', '300');
        set_time_limit(300);

        $now = Carbon::now();

        $v_ip_address = $_SERVER['REMOTE_ADDR'];

        $plsql = "
        DECLARE
            V_LOG_ID number := TO_NUMBER(TO_CHAR(SYSDATE, 'YYYYMMDDHH24MISS'));
            V_DATE DATE := SYSDATE;
            v_user varchar2(50) := null;
            v_ip_address varchar2(50) := '{$v_ip_address}';
        BEGIN
            INSERT INTO usraes.prg_sigrei_log(imei,estado_del_reporte,fecha_reporte,log_id,log_date,log_user,log_ip_address)
            select imei,estado_del_reporte,fecha_reporte, v_log_id, v_date, v_user, v_ip_address from reporte_sigrei_tmp;
            commit;

            execute immediate 'DROP TABLE TMP_IMEI';
            execute immediate 'create table tmp_imei (contador number,imei varchar2(50),tipo varchar2(50),fecha DATE)';

            INSERT INTO tmp_imei(contador,IMEI, tipo, fecha)
            select contador,LPAD(to_char(imei), 15, '0') as imei,estado_del_reporte,to_date(fecha_reporte, 'dd/mm/yyyy hh24:mi:ss') from reporte_sigrei_tmp;
            commit;

            -- Crear tabla temporal para guardar la data del cruce del cruce de la tabla temporal TMP_IMEI con la tabla dws.SA_ter_claro_movimiento.

            execute immediate 'DROP TABLE tmp_imei_1';
            execute immediate 'create table tmp_imei_1 as
            select a.contador,a.imei,a.tipo,a.fecha,''51''||b.ter_numerolinea msisdn,b.Ter_estado,b.ter_des_motivo from tmp_imei A LEFT JOIN  
            --dws.SA_ter_claro_movimiento B
            dm.ter_claro_movimiento B
            ON A.IMEI=B.NUMERO_IMEI
            and to_char(a.fecha,''dd/mm/yyyy'')=to_char(b.ter_fecregistro,''dd/mm/yyyy'') AND TER_ESTADO=''BLOQUEADO''';

            
            -- Crear tabla temporal para guardar la data del cruce de la tabla temporal tmp_imei_1 con la tabla DM.F_M_ABONADOS

            execute immediate 'drop table tmp_final';
            execute immediate 'create table tmp_final as
            select A.*,B.MES,B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO,B.NOMBRES,B.APELLIDOS from tmp_imei_1 A
            LEFT JOIN  DM.F_M_ABONADOS B ON A.MSISDN=B.MSISDN AND B.MES=TO_CHAR(FECHA,''YYYYMM'')';

            -- Actualizar la tabla temporal tmp_final

            merge into tmp_final a
            using (SELECT DISTINCT A.MSISDN,A.TER_ESTADO,A.TER_DES_MOTIVO,B.MES,B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO,B.NOMBRES,B.APELLIDOS
            FROM tmp_final A INNER JOIN dm.f_M_abonados B ON A.MSISDN=B.MSISDN WHERE TO_CHAR(add_months(FECHA,-1),'YYYYMM')=B.MES AND  A.NOMBRES IS NULL ) b
            on (a.MSISDN=b.MSISDN)
            when matched then
            update set a.MES=B.MES,
                a.TIPO_DOCUMENTO= b.TIPO_DOCUMENTO,
                A.NRO_DOCUMENTO=B.NRO_DOCUMENTO,
                A.NOMBRES=B.NOMBRES,
                A.APELLIDOS=B.APELLIDOS
                where A.NOMBRES IS NULL;
            COMMIT;

            -- Agregar columna

            execute immediate 'ALTER  TABLE tmp_final ADD (RAZON_SOCIAL VARCHAR2(200))';

            -- Actualizar la tabla temporal tmp_final

            merge into tmp_final a
            using ( SELECT * FROM(  SELECT T.*,ROW_NUMBER () OVER(PARTITION BY T.NRO_DOCUMENTO ORDER BY T.CUSTOMER_ID DESC) AS FLAG FROM (
            SELECT DISTINCT A.*,B.CCNAME, B.CUSTOMER_ID
            FROM tmp_final A INNER JOIN DWS.SA_CCONTACT_ALL B ON B.CSCOMPREGNO=A.NRO_DOCUMENTO WHERE  A.TIPO_DOCUMENTO='RUC')T
            )
            WHERE FLAG='1'
            ) b
            on (a.NRO_DOCUMENTO=b.NRO_DOCUMENTO)
            when matched then
            update set A.RAZON_SOCIAL=B.CCNAME
                where A.TIPO_DOCUMENTO ='RUC';
            COMMIT;

            -- Crear tabla temporal tmp_final_1

            execute immediate 'DROP TABLE tmp_final_1';
            
            execute immediate 'create table tmp_final_1 as
            select A.CONTADOR,A.IMEI,A.TIPO,A.FECHA,TO_NUMBER(substr(A.MSISDN,3,12)) NUMERO_SERVICIO_TELEFONICO,A.TIPO_DOCUMENTO,
            CASE WHEN TIPO_DOCUMENTO =''DNI'' THEN ''01''
                WHEN  TIPO_DOCUMENTO =''RUC'' THEN ''02''
                WHEN  TIPO_DOCUMENTO =''C.E.'' THEN ''03''
                WHEN  UPPER(TIPO_DOCUMENTO) =''PASAPORTE'' THEN ''04'' END TIPO_DOC,LTRIM(A.NRO_DOCUMENTO) NRO_DOCUMENTO,
                    TRIM(A.NOMBRES) NOMBRES,TRIM(SUBSTR(A.APELLIDOS,0,INSTR(A.APELLIDOS,'' ''))) APELLIDO_PATERNO,
            TRIM(SUBSTR(A.APELLIDOS,INSTR(A.APELLIDOS,'' ''))) APELLIDO_MATERNO ,  A.RAZON_SOCIAL
            from tmp_final A';


            -- Crear tabla temporal tmp_final_2

            execute immediate 'DROP TABLE TMP_FINAL_2';
            
            execute immediate 'CREATE TABLE TMP_FINAL_2 AS
            SELECT CONTADOR,IMEI,TIPO,FECHA,NUMERO_SERVICIO_TELEFONICO,
            CASE WHEN APELLIDO_PATERNO IS NULL THEN ''.''
            WHEN  APELLIDO_PATERNO IS NOT NULL THEN APELLIDO_PATERNO
            END APELLIDO_PATERNO,
            CASE WHEN APELLIDO_MATERNO IS NULL THEN ''.''
            WHEN  APELLIDO_MATERNO IS NOT NULL THEN APELLIDO_MATERNO
            END APELLIDO_MATERNO,NOMBRES,TIPO_DOC TIPO_DOC,NRO_DOCUMENTO,RAZON_SOCIAL
            FROM tmp_final_1';

            insert into usraes.prg_sigrei_final_log(
            imei, fecha, numero_servicio_telefonico, apellido_paterno, apellido_materno, nombres, tipo_doc, nro_documento, razon_social,
            log_id, log_date, log_user, log_ip_address)
            select imei, fecha, numero_servicio_telefonico, apellido_paterno, apellido_materno, nombres, tipo_doc, nro_documento, razon_social,
            v_log_id, v_date, v_user, v_ip_address
            from TMP_FINAL_2;
            COMMIT;
        END;";

        /*try {
            DB::connection('oracle_dwo')->statement(DB::Raw($plsql));
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception('Ocurrió un error al generar el archivo: '.$th->getMessage());
        }*/
        $this->generar_reporte();

        return Excel::download(new ReporteSigreiExport, 'reporte_sigrei.xlsx');

        /*return (new ReporteSigreiExport)->download('reporte_sigrei.csv', \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);*/
    }

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
            select imei,estado_del_reporte,fecha_reporte, {$v_log_id}, to_date('{$v_date}', 'YYYY-MM-DD HH24:MI:SS'), null, '{$v_ip_address}' from reporte_sigrei_tmp;
            commit;

            EXECUTE IMMEDIATE 'DROP TABLE TMP_IMEI';
            EXECUTE IMMEDIATE 'create table tmp_imei (contador number,imei varchar2(50),tipo varchar2(50),fecha DATE)';

            INSERT INTO tmp_imei(contador,IMEI, tipo, fecha)
            select contador,LPAD(to_char(imei), 15, '0') as imei,estado_del_reporte,to_date(fecha_reporte, 'dd/mm/yyyy hh24:mi:ss') from reporte_sigrei_tmp;
            commit;

            EXECUTE IMMEDIATE 'DROP TABLE tmp_imei_1';
            EXECUTE IMMEDIATE 'create table tmp_imei_1 as
            select a.contador,a.imei,a.tipo,a.fecha,''51''||b.ter_numerolinea msisdn,b.Ter_estado,b.ter_des_motivo from tmp_imei A LEFT JOIN
            dm.ter_claro_movimiento B
            ON A.IMEI=B.NUMERO_IMEI
            and to_char(a.fecha,''dd/mm/yyyy'')=to_char(b.ter_fecregistro,''dd/mm/yyyy'') AND TER_ESTADO=''BLOQUEADO''';
            COMMIT;

            EXECUTE IMMEDIATE 'DROP TABLE tmp_final';
            EXECUTE IMMEDIATE 'create table tmp_final as
            select A.*,B.MES,B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO,B.NOMBRES,B.APELLIDOS from tmp_imei_1 A
            LEFT JOIN  DM.F_M_ABONADOS B ON A.MSISDN=B.MSISDN AND B.MES=TO_CHAR(FECHA,''YYYYMM'')';
            COMMIT;

            merge into tmp_final a
            using (SELECT DISTINCT A.MSISDN,A.TER_ESTADO,A.TER_DES_MOTIVO,B.MES,B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO,B.NOMBRES,B.APELLIDOS
            FROM tmp_final A INNER JOIN dm.f_M_abonados B ON A.MSISDN=B.MSISDN WHERE TO_CHAR(add_months(FECHA,-1),'YYYYMM')=B.MES AND  A.NOMBRES IS NULL ) b
            on (a.MSISDN=b.MSISDN)
            when matched then
            update set a.MES=B.MES,
                a.TIPO_DOCUMENTO= b.TIPO_DOCUMENTO,
                A.NRO_DOCUMENTO=B.NRO_DOCUMENTO,
                A.NOMBRES=B.NOMBRES,
                A.APELLIDOS=B.APELLIDOS
                where A.NOMBRES IS NULL;
            COMMIT;

        END;";


        $plsql[] = "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_NOMBRES_NOT_NULL';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN
                RAISE;
            END IF;
        END;";
        $plsql[] = "CREATE TABLE USRAES.TMP_NOMBRES_NOT_NULL(
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
            SELECT TO_CHAR(FECHA,'YYYYMM') AS MES, MSISDN FROM tmp_final WHERE NOMBRES IS NULL;
        BEGIN
            FOR V_ROW IN CUR_NOMBRES_NULL
            LOOP
                SELECT COUNT(*) INTO V_COUNTER FROM USRAES.TMP_NOMBRES_NOT_NULL WHERE MSISDN = V_ROW.MSISDN;
                IF V_COUNTER = 0 THEN
                    EXECUTE IMMEDIATE 'INSERT INTO USRAES.TMP_NOMBRES_NOT_NULL(MSISDN,MES,TIPO_DOCUMENTO,NRO_DOCUMENTO,NOMBRES,APELLIDOS)
                    SELECT
                    s.subscription_access_number MSISDN,
                    S.PERIOD MES,
                    S.ID_CARD_TYPE_VALUE TIPO_DOCUMENTO,
                    S.ID_CARD_VALUE NRO_DOCUMENTO,
                    S.CUSTOMER_FIRST_NAME NOMBRES,
                    S.CUSTOMER_LAST_NAME APELLIDOS 
                    FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION (P_'||V_ROW.MES||') S
                    where 
                    s.subscription_access_number in ('''|| V_ROW.MSISDN ||''')
                    AND s.subscription_status <> ''D''
                    AND s.subscription_status <> ''S''
                    ';
                END IF;

                SELECT COUNT(*) INTO V_COUNTER FROM USRAES.TMP_NOMBRES_NOT_NULL WHERE MSISDN = V_ROW.MSISDN;
                
                IF V_COUNTER = 0 THEN
                    EXECUTE IMMEDIATE 'INSERT INTO USRAES.TMP_NOMBRES_NOT_NULL(MSISDN,MES,TIPO_DOCUMENTO,NRO_DOCUMENTO,NOMBRES,APELLIDOS)
                    SELECT
                    s.subscription_access_number MSISDN,
                    S.PERIOD MES,
                    S.ID_CARD_TYPE_VALUE TIPO_DOCUMENTO,
                    S.ID_CARD_VALUE NRO_DOCUMENTO,
                    S.CUSTOMER_FIRST_NAME NOMBRES,
                    S.CUSTOMER_LAST_NAME APELLIDOS 
                    FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION (P_'||TO_CHAR(TO_DATE(V_ROW.MES, 'YYYYMM') - INTERVAL '1' MONTH, 'YYYYMM')||') S
                    where 
                    s.subscription_access_number in ('''|| V_ROW.MSISDN ||''')
                    AND s.subscription_status <> ''D''
                    AND s.subscription_status <> ''S''
                    ';
                END IF;
                
                COMMIT;
        
            END LOOP;
        
            MERGE INTO tmp_final a
            USING (
            SELECT * FROM USRAES.TMP_NOMBRES_NOT_NULL
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
        
        END;";


        $plsql[] = "ALTER TABLE tmp_final ADD (RAZON_SOCIAL VARCHAR2(200))";
        $plsql[] = "BEGIN
        merge into tmp_final a
        using ( SELECT * FROM(  SELECT T.*,ROW_NUMBER () OVER(PARTITION BY T.NRO_DOCUMENTO ORDER BY T.CUSTOMER_ID DESC) AS FLAG FROM (
        SELECT DISTINCT A.*,B.CCNAME, B.CUSTOMER_ID
        FROM tmp_final A INNER JOIN DWS.SA_CCONTACT_ALL B ON B.CSCOMPREGNO=A.NRO_DOCUMENTO WHERE  A.TIPO_DOCUMENTO='RUC')T
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
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE tmp_final_1';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;

            EXECUTE IMMEDIATE 'create table tmp_final_1 as
            select A.CONTADOR,A.IMEI,A.TIPO,A.FECHA,TO_NUMBER(substr(A.MSISDN,3,12)) NUMERO_SERVICIO_TELEFONICO,A.TIPO_DOCUMENTO,
            CASE WHEN TIPO_DOCUMENTO =''DNI'' THEN ''01''
                WHEN  TIPO_DOCUMENTO =''RUC'' THEN ''02''
                WHEN  TIPO_DOCUMENTO =''C.E.'' THEN ''03''
                WHEN  UPPER(TIPO_DOCUMENTO) =''PASAPORTE'' THEN ''04'' END TIPO_DOC,LTRIM(A.NRO_DOCUMENTO) NRO_DOCUMENTO,
                    TRIM(A.NOMBRES) NOMBRES,TRIM(SUBSTR(A.APELLIDOS,0,INSTR(A.APELLIDOS,'' ''))) APELLIDO_PATERNO,
            TRIM(SUBSTR(A.APELLIDOS,INSTR(A.APELLIDOS,'' ''))) APELLIDO_MATERNO ,  A.RAZON_SOCIAL
            from tmp_final A';
            COMMIT;

            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE TMP_FINAL_2';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;

            EXECUTE IMMEDIATE 'CREATE TABLE TMP_FINAL_2 AS
            SELECT CONTADOR,IMEI,TIPO,FECHA,NUMERO_SERVICIO_TELEFONICO,
            CASE WHEN APELLIDO_PATERNO IS NULL THEN ''.''
            WHEN  APELLIDO_PATERNO IS NOT NULL THEN APELLIDO_PATERNO
            END APELLIDO_PATERNO,
            CASE WHEN APELLIDO_MATERNO IS NULL THEN ''.''
            WHEN  APELLIDO_MATERNO IS NOT NULL THEN APELLIDO_MATERNO
            END APELLIDO_MATERNO,NOMBRES,TIPO_DOC TIPO_DOC,NRO_DOCUMENTO,RAZON_SOCIAL
            FROM tmp_final_1';
            COMMIT;

            insert into usraes.prg_sigrei_final_log(
            imei, fecha, numero_servicio_telefonico, apellido_paterno, apellido_materno, nombres, tipo_doc, nro_documento, razon_social,
            log_id, log_date, log_user, log_ip_address)
            select imei, fecha, numero_servicio_telefonico, apellido_paterno, apellido_materno, nombres, tipo_doc, nro_documento, razon_social,
            {$v_log_id}, to_date('{$v_date}', 'YYYY-MM-DD HH24:MI:SS'), null, '{$v_ip_address}'
            from TMP_FINAL_2;
            COMMIT;
        END;";

        $resp = [];
        foreach($plsql as $sql){
            $resp[] = DB::statement(DB::Raw($sql));
        }
        return $resp;
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
        $data = DB::connection('oracle_dwo')->table('reporte_sigrei_tmp')->get();
        return response()->json($data);
    }
}