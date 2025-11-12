<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaTipoReporte;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentExtraccionDevFijaRepository implements ExtraccionDevFijaRepository
{
    private $userIdentifier;
    private $authService;
    private $connection = "oracle";
    private $chDb;

    public function __construct(ClickhouseDB $chDb, AuthService $authService)
    {
        $this->authService = $authService;
        $this->chDb = $chDb->connection("ch-dn05");
        $this->authService = $authService;
    }

    public function processAndGetGruposUsuario($distritos, $ticket, $servicioAfectado, DateTime $fechaIni, DateTime $fechaFin, $mesesInteres)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $partitionActual = (new DateTime())->format('Ym');
        $strFechaIniF1 = $fechaIni->format("Y-m-d H:i:s");
        $strFechaFinF1 = $fechaFin->format("Y-m-d H:i:s");
        $strFechaIniF2 = $fechaIni->format("d/m/Y H:i:s");
        $strFechaFinF2 = $fechaFin->format("d/m/Y H:i:s");
        $fechaDevolucion = (new DateTime())->modify("+{$mesesInteres} month");
        $strFechaDevolucion = $fechaDevolucion->format("Ymd");
        $tipo_cambio = null;
        $factorAcumulado = 'null';
        $factorAcumulado2 = 'null';

        $result = DB::connection("oracle_reptdm")->select(DB::raw("SELECT T.TIPO_CAMBIO FROM DM.SA_DEVOLUCION_TIPOCAMBIO T 
        WHERE :p_year = T.PERIODO"), ["p_year" => $fechaIni->format("Y")]);
        if(count($result)>0){
            $tipo_cambio = $result[0]->tipo_cambio;
        }

        $result = DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT FACTORACUMULADO FROM USRAES.DWH_TASAINTERES_SBS WHERE FECHA= TRUNC(SYSDATE -1, 'DD')"));
        if(count($result)>0){
            $factorAcumulado = $result[0]->factoracumulado;
        }

        $result = DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT FACTORACUMULADO FROM USRAES.DWH_TASAINTERES_SBS
        WHERE FECHA= TRUNC(SYSDATE -1, 'DD') - (TO_DATE('{$strFechaDevolucion}','YYYYMMDD') - TRUNC(SYSDATE -1, 'DD') )"));
        if(count($result)>0){
            $factorAcumulado2 = $result[0]->factoracumulado;
        }

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier}(
            TICKET VARCHAR2(100),
            DEPARTAMENTO VARCHAR2(100),
            PROVINCIA VARCHAR2(100),
            DISTRITO VARCHAR2(100),
            --PLANOS VARCHAR2(100),
            SERVICIO_AFECTADO VARCHAR2(250),
            FECHA_INI DATE,
            --HORA_INI DATE,
            FECHA_FIN DATE,
            --HORA_FIN DATE,
            MESES NUMBER
        )"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.input_devo_fija_plano_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.input_devo_fija_plano_{$this->userIdentifier}(
            ticket  VARCHAR2(100),
            DEPARTAMENTO VARCHAR2(100),
            provincia VARCHAR2(100),
            distrito VARCHAR2(100),
            plano  VARCHAR2(100)
        )"];

        $this->exec_sql($queries);

        /*DB::table("USRAES.input_devo_fija_plano_{$this->userIdentifier}")
        ->where("ticket", $ticket)
        ->delete();*/

        for ($i=0; $i < count($distritos); $i++) {
            /*
            DB::table("USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier}")
            ->where("ticket", $ticket)
            ->where("departamento", $distritos[$i][0])
            ->delete();
            */

            DB::table("USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier}")
            ->insert([
                "ticket" => $ticket,
                "departamento" => $distritos[$i][0],
                "provincia" => $distritos[$i][1],
                "distrito" => $distritos[$i][2],
                "servicio_afectado" => $servicioAfectado,
                "fecha_ini" => $strFechaIniF1,
                "fecha_fin" => $strFechaFinF1,
                "meses" => $mesesInteres,
            ]);
            

            foreach ($distritos[$i][3] as $plano) {
                DB::table("USRAES.input_devo_fija_plano_{$this->userIdentifier}")
                ->insert([
                    "departamento" => $distritos[$i][0],
                    "provincia" => $distritos[$i][1],
                    "distrito" => $distritos[$i][2],
                    "ticket" => $ticket,
                    "plano" => $plano,
                ]);
            }

        }

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_ABONADOS_NODOS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_ABONADOS_NODOS_{$this->userIdentifier} (
            CODCLI VARCHAR2(20),
            CODSUC VARCHAR2(10),
            IDPLANO VARCHAR2(10)
        )"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_INSSRV_SERVAFEC_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_INSSRV_SERVAFEC_{$this->userIdentifier} (
            CID NUMBER,
            NUMERO VARCHAR2(25),
            ESTINSSRV NUMBER,
            CODINSSRV NUMBER,
            CODCLI VARCHAR2(50),
            TIPSRV VARCHAR2(50),
            FCHINI_INST DATE,
            FCHFIN_INST DATE,
            DIRECCION VARCHAR2(1000),
            SEDE VARCHAR2(250),
            CODUBI VARCHAR2(50),
            CODSUC VARCHAR2(50),
            IDPLANO VARCHAR2(50),
            CO_ID NUMBER,
            NUMSEC VARCHAR2(50),
            CUSTOMER_ID NUMBER
        )"];

        $informeFalla = DB::table("usraes.noc_informe_de_fallas_fija")
        ->where("ticket", $ticket)
        ->first();

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_INSSRV_SRVAF_TYP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];

        if ($informeFalla !== null && (int) $informeFalla->tipo_reporte === InformeFijaTipoReporte::BY_CODCLI) {
            $queries[] = ["sql" => "BEGIN
                INSERT INTO USRAES.TMP_ABONADOS_NODOS_{$this->userIdentifier}(CODCLI)
                SELECT CODCLI FROM usraes.noc_informe_de_fallas_fija_codcli
                where num_reporte = :num_reporte;
                commit;
            END;", "params" => ["num_reporte" => $informeFalla->numero_reporte]];

            $queries[] = ["sql" => "DECLARE
                CURSOR CUR_TICKETS IS
                SELECT TICKET, SERVICIO_AFECTADO FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET, SERVICIO_AFECTADO;
            BEGIN
                FOR V_ROW IN CUR_TICKETS
                LOOP
                    INSERT INTO USRAES.TMP_INSSRV_SERVAFEC_{$this->userIdentifier}(
                    CID, NUMERO, ESTINSSRV, CODINSSRV, CODCLI, TIPSRV, FCHINI_INST, FCHFIN_INST,
                    DIRECCION, SEDE, CODUBI, CODSUC, IDPLANO, CO_ID, NUMSEC, CUSTOMER_ID
                    )
                    SELECT I.CID,I.NUMERO,I.ESTINSSRV,I.CODINSSRV,I.CODCLI,I.TIPSRV,
                    I.FECINI FCHINI_INST,I.FECFIN FCHFIN_INST,
                    I.DIRECCION,I.DESCRIPCION SEDE,I.CODUBI,
                    J.CODSUC,J.IDPLANO,I.CO_ID,
                        I.NUMSEC,I.CUSTOMER_ID
                    FROM USRAES.TMP_ABONADOS_NODOS_{$this->userIdentifier} J
                    JOIN DWS.SA_SOLOT ST
                    ON J.CODCLI=ST.CUSTOMER_ID
                    JOIN dws.sa_inssrv I
                    ON ST.CODCLI = I.CODCLI
                    WHERE I.TIPSRV IN (
                        SELECT DISTINCT E.TIPSRV
                        FROM USRAES.SA_DEVOLUCION_EQUIVALENCIAS E
                        WHERE OSIPTEL LIKE '%'|| V_ROW.SERVICIO_AFECTADO ||'%'
                    );
                    COMMIT;
                END LOOP;
            END;"];

            $queries[] = ["sql" => "CREATE TABLE  USRAES.TMP_INSSRV_SRVAF_TYP_{$this->userIdentifier}  NOLOGGING   AS
            SELECT i.*, Y.DSCTIPSRV   SERVICIO,
                '-' NOMPVC, '-' NOMEST, '-' NOMDST
            FROM USRAES.TMP_INSSRV_SERVAFEC_{$this->userIdentifier} i
            LEFT JOIN dws.sa_tystipsrv Y
                ON I.TIPSRV = Y.TIPSRV"];
            $queries[] = ["sql" => "BEGIN
                UPDATE USRAES.TMP_INSSRV_SRVAF_TYP_{$this->userIdentifier} SET
                NOMPVC='', NOMEST='', NOMDST='';
                COMMIT;
            END;"];
        } else {
            $queries[] = ["sql" => "DECLARE
                CURSOR CUR_TICKETS IS
                SELECT TICKET, SERVICIO_AFECTADO FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET, SERVICIO_AFECTADO;
            BEGIN
                FOR V_ROW IN CUR_TICKETS
                LOOP
                    INSERT INTO USRAES.TMP_ABONADOS_NODOS_{$this->userIdentifier}(CODCLI, CODSUC, IDPLANO)
                    SELECT CODCLI,CODSUC,IDPLANO
                    FROM
                    (
                        SELECT TRIM(CODCLI) CODCLI, TRIM(CODSUC) CODSUC, IDPLANO, ROW_NUMBER() OVER (PARTITION BY CODCLI ORDER BY FECULTACT DESC) ORDEN
                        FROM dws.sa_vtasuccli
                        WHERE IDPLANO IN (
                            SELECT TRIM(PLANO) FROM USRAES.input_devo_fija_plano_{$this->userIdentifier} WHERE TICKET = V_ROW.TICKET
                        )
                    ) X
                    WHERE ORDEN=1;
                    COMMIT;
                END LOOP;

                FOR V_ROW IN CUR_TICKETS
                LOOP

                    INSERT INTO USRAES.TMP_INSSRV_SERVAFEC_{$this->userIdentifier}(
                    CID, NUMERO, ESTINSSRV, CODINSSRV, CODCLI, TIPSRV, FCHINI_INST, FCHFIN_INST,
                    DIRECCION, SEDE, CODUBI, CODSUC, IDPLANO, CO_ID, NUMSEC, CUSTOMER_ID
                    )
                    SELECT I.CID,I.NUMERO,I.ESTINSSRV,I.CODINSSRV,I.CODCLI,I.TIPSRV,
                    I.FECINI FCHINI_INST,I.FECFIN FCHFIN_INST,
                    I.DIRECCION,I.DESCRIPCION SEDE,I.CODUBI,
                    J.CODSUC,J.IDPLANO,I.CO_ID,
                        I.NUMSEC,I.CUSTOMER_ID
                    FROM dws.sa_inssrv I
                    JOIN USRAES.TMP_ABONADOS_NODOS_{$this->userIdentifier} J
                    ON J.CODCLI = I.CODCLI
                    AND J.CODSUC = I.CODSUC
                    WHERE I.TIPSRV IN (
                        SELECT DISTINCT E.TIPSRV
                        FROM USRAES.SA_DEVOLUCION_EQUIVALENCIAS E
                        WHERE OSIPTEL LIKE '%'|| V_ROW.SERVICIO_AFECTADO ||'%'
                    );
                    COMMIT;
                END LOOP;
            END;"];

            $queries[] = ["sql" => "CREATE TABLE  USRAES.TMP_INSSRV_SRVAF_TYP_{$this->userIdentifier}  NOLOGGING   AS
            SELECT i.*, Y.DSCTIPSRV   SERVICIO,
                U.NOMPVC,U.NOMEST,U.NOMDST
            FROM USRAES.TMP_INSSRV_SERVAFEC_{$this->userIdentifier} i
            LEFT JOIN dws.sa_tystipsrv Y
                ON I.TIPSRV = Y.TIPSRV
            LEFT JOIN dws.sa_v_ubicaciones U
                ON I.CODUBI = U.CODUBI"];
        }
        
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_SERVAFEC_INSPRD_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_SERVAFEC_INSPRD_{$this->userIdentifier} NOLOGGING PARALLEL 8 AS --PRODUCTOS SEGÚN CODINSSRV
        SELECT
            I.CID,I.NUMERO,I.ESTINSSRV,
            I.CODINSSRV,I.CODCLI,I.TIPSRV,I.FCHINI_INST,I.FCHFIN_INST,
            I.DIRECCION,I.SEDE,I.CODUBI,I.SERVICIO,I.CODSUC,I.IDPLANO,
            I.NOMPVC,I.NOMEST,I.NOMDST,I.CO_ID,I.NUMSEC,I.CUSTOMER_ID,
            P.PID,P.DESCRIPCION,P.CODSRV,P.FECINI,P.FECFIN,
            P.ESTINSPRD,P.NUMSLC
        FROM USRAES.TMP_INSSRV_SRVAF_TYP_{$this->userIdentifier} I
        JOIN dws.sa_insprd P
        ON I.CODINSSRV = P.CODINSSRV"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_INSSR_PRODUCT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE  USRAES.TMP_INSSR_PRODUCT_{$this->userIdentifier} NOLOGGING AS
        SELECT --N.TRIMESTRE,
        '{$ticket}' TICKET, P.*,SR.DSCSRV,
        '$strFechaIniF2' FEC_INI_INCIDENCIA,
        '$strFechaFinF2' FEC_FIN_INCIDENCIA
        FROM   USRAES.TMP_SERVAFEC_INSPRD_{$this->userIdentifier} P
        LEFT JOIN dws.sa_tystabsrv SR
        ON P.CODSRV = SR.CODSRV
        WHERE P.FECINI < TRUNC(TO_DATE('$strFechaFinF2', 'DD/MM/YYYY HH24:MI:SS'), 'DD')
            AND (P.FECFIN > TRUNC(TO_DATE('$strFechaIniF2', 'DD/MM/YYYY HH24:MI:SS'), 'DD') OR
                P.FECFIN IS NULL)"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => " CREATE TABLE USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} NOLOGGING PARALLEL 8 as --SE OBTIENE INFORMACIÓN DEL CLIENTE
        SELECT --I.TRIMESTRE,
            I.TICKET,
            CL.CODSECMARK    COD_SECTOR,S.DSCSECMARK    SECTOR,
            I.CID,I.NUMERO,I.SERVICIO,I.ESTINSSRV,I.CODINSSRV,I.CODCLI,
            I.TIPSRV,I.FCHINI_INST,I.FCHFIN_INST,
            I.DIRECCION,I.DESCRIPCION  SEDE,CL.NOMCLI,CL.NTDIDE,
            TI.DESCINT  TIPDOC,I.CODUBI,I.NOMPVC,I.NOMEST,I.NOMDST,
            I.PID,I.DESCRIPCION,I.CODSRV,I.DSCSRV,I.FECINI, I.FECFIN,
            I.ESTINSPRD,I.NUMSLC,I.CODSUC,I.IDPLANO,FEC_INI_INCIDENCIA,
            I.FEC_FIN_INCIDENCIA,I.CO_ID,I.NUMSEC,I.CUSTOMER_ID --- TRES CAMPOS AGREGADOS PARA PODER VALIDAR DATOS QUE PROVIENEN DESDE BSCS
        FROM USRAES.TMP_INSSR_PRODUCT_{$this->userIdentifier} I
        JOIN dws.sa_vtatabcli CL
        ON I.CODCLI = CL.CODCLI
        LEFT JOIN dws.sa_vtatabsecmark S
        ON CL.CODSECMARK = S.CODSECMARK
        LEFT JOIN dws.sa_vtatipdid TI
        ON CL.TIPDIDE = TI.TIPDIDE"];

        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.UNIQUE_CODINSSRV_TMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.UNIQUE_CODINSSRV_TMP_{$this->userIdentifier} NOLOGGING PARALLEL 8 as 
        select /*+ PARALLEL(4)*/ CODINSSRV,SUBSTR(FEC_INI_INCIDENCIA,7,4)||'-'||SUBSTR(FEC_INI_INCIDENCIA,4,2)||'-'||SUBSTR(FEC_INI_INCIDENCIA,1,2) FEC_INI_INCIDENCIA,SUBSTR(FEC_FIN_INCIDENCIA,7,4)||'-'||SUBSTR(FEC_FIN_INCIDENCIA,4,2)||'-'||SUBSTR(FEC_FIN_INCIDENCIA,1,2) FEC_FIN_INCIDENCIA from USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} GROUP BY CODINSSRV,FEC_INI_INCIDENCIA,FEC_FIN_INCIDENCIA"];

        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.CORRECTO_SGA_BSCS_TMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.CORRECTO_SGA_BSCS_TMP_{$this->userIdentifier} NOLOGGING PARALLEL 8 as 
        SELECT * FROM (
        SELECT AA.*,row_number() OVER(PARTITION BY AA.CODINSSRV ORDER BY AA.FECUSU DESC) FLAG,CASE WHEN COD_ID_BSCS IS NOT NULL THEN 'BSCS' WHEN COD_ID_BSCS IS NULL THEN 'SGA' END FUENTE
        FROM (
        SELECT AA.*,BB.CODCLI CODCLI_V2,BB.CUSTOMER_ID CUSTOMER_ID_BSCS,BB.COD_ID COD_ID_BSCS FROM (
        select aa.*,bb.CODSOLOT,bb.FECUSU from 
        (select /*+ PARALLEL(4)*/ * from USRAES.UNIQUE_CODINSSRV_TMP_{$this->userIdentifier}) aa 
        LEFT join DWS.SA_SOLOTPTO bb 
        on aa.FEC_INI_INCIDENCIA>=to_char(bb.FECUSU,'YYYY-MM-DD') and 
        aa.CODINSSRV=bb.CODINSSRV) AA 
        LEFT JOIN DWS.SA_SOLOT BB 
        ON AA.CODSOLOT=BB.CODSOLOT AND BB.ESTSOL IN (12,29) AND AA.FEC_INI_INCIDENCIA>=to_char(BB.FECUSU,'YYYY-MM-DD')
        ) AA) WHERE FLAG=1"];

        $queries[] = ["sql" => "BEGIN
        MERGE INTO USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} A
        USING USRAES.CORRECTO_SGA_BSCS_TMP_{$this->userIdentifier} B
        ON (A.CODINSSRV=B.CODINSSRV)
        WHEN MATCHED THEN
        UPDATE SET A.CO_ID= B.COD_ID_BSCS,
                    A.CUSTOMER_ID= B.CUSTOMER_ID_BSCS;
        commit;
        END;"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_INSTXPROD_MASIV_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_INSTXPROD_MASIV_{$this->userIdentifier}  NOLOGGING PARALLEL 8 AS
        SELECT --B.TRIMESTRE,
            B.TICKET,B.CODINSSRV,B.DESCRIPCION SEDE,B.DIRECCION DIREC_INSTALACION,
            B.CODCLI,B.NOMCLI,B.NTDIDE NRO_DOC,B.TIPDOC,B.NUMERO,B.CID,B.TIPSRV,B.SERVICIO FAMILIA,
            B.FCHINI_INST,B.FCHFIN_INST,B.ESTINSSRV,B.FEC_INI_INCIDENCIA,B.FEC_FIN_INCIDENCIA,
            (TO_DATE(B.FEC_FIN_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS') -
            TO_DATE(B.FEC_INI_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS')) * 24 * 60 MINUTOS_AFECTACION,
            B.NOMEST DPTO,B.NOMPVC PROVINCIA,B.NOMDST DISTRITO,B.IDPLANO, B.CODSUC,B.SECTOR,
            B.NUMSLC,B.PID,B.CODSRV, B.DSCSRV,B.FECINI FECINI_INSPROD,
            B.FECFIN FECFIN_INSPROD,B.ESTINSPRD ESTADO_INSTPROD,
            Y.CODCLI CODCLI_INST,Y.IDINSTPROD,Y.DESCRIPCION DESCRIPCION_INST,
            Y.IDPRODUCTO,ROUND(Y.MONTOCR, 2) MONTOCR_PROD,
            DECODE(Y.IDMONEDACR, 1, 'SOLES', 2, 'DOLARES') MONEDA,
            Y.CICFAC,Y.ESTADO,Y.FECINI,Y.FECFIN
        FROM dws.sa_instxproducto Y
        JOIN USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} B
        ON Y.CODCLI = B.CODCLI
        AND Y.PID = B.PID
        WHERE CO_ID IS NULL
        AND Y.MONTOCR > 0"];

        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "DECLARE
            V_SERV VARCHAR2(60);
            V_SQL  VARCHAR2(4000);
        BEGIN
            V_SERV := UPPER(TRANSLATE('{$servicioAfectado}','áéíóúÁÉÍÓÚñÑ','aeiouAEIOUnN'));
        
            IF (V_SERV <> 'TELEFONIA FIJA LOCAL') THEN
                V_SQL:='
                CREATE TABLE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} NOLOGGING PARALLEL 8 AS
                (
                SELECT Y.*, PR.DESCRIPCION DESC_PRODUCTO
                FROM USRAES.TMP_INSTXPROD_MASIV_{$this->userIdentifier} Y
                JOIN dws.sa_producto PR
                    ON Y.IDPRODUCTO = PR.IDPRODUCTO
                WHERE  PR.IDGRUPOPRODUCTO IS NULL AND PR.NIVEL = 2 AND
                PR.IDMONEDACR IS NOT NULL AND PR.FLGDEVINTP = 1
                )';
                EXECUTE IMMEDIATE V_SQL;
            ELSE
                V_SQL:='CREATE TABLE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} NOLOGGING PARALLEL 8 AS
                (
                SELECT Y.*, PR.DESCRIPCION DESC_PRODUCTO
                FROM USRAES.TMP_INSTXPROD_MASIV_{$this->userIdentifier} Y
                JOIN dws.sa_producto PR
                    ON Y.IDPRODUCTO = PR.IDPRODUCTO
                )';
                EXECUTE IMMEDIATE V_SQL;
            END IF;
        END;"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_SPCODE_BSCS_HIST_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TABLA_DWH_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_BSCS_CF_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "DECLARE
            STR VARCHAR2(2000);
            V_CANTIDAD NUMBER;
            V_MESACTUAL CHAR(6);
        BEGIN
            SELECT COUNT(1),TO_CHAR(SYSDATE,'YYYYMM') INTO V_CANTIDAD,V_MESACTUAL
            FROM USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} WHERE CO_ID IS NOT NULL;
        
        IF V_CANTIDAD>0 THEN
                STR:=
                'CREATE TABLE USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier} NOLOGGING PARALLEL 4 as 
                SELECT * FROM (
                SELECT T.TICKET,T.CODINSSRV,T.DESCRIPCION,
                F.CUSTOMER_ACCOUNT_BILLING_ADDRESS,T.CODCLI,F.CUSTOMER_ACCOUNT_SC CUSTOMER_ID,F.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO,F.CUSTOMER_FULL_NAME RAZON_SOCIAL,
                F.ID_CARD_VALUE NRO_DOCUMENTO,
                F.ID_CARD_TYPE_VALUE TIPDOC_CLIENTE,
                T.NUMERO,T.CID,T.TIPSRV,T.SERVICIO FAMILIA,F.AGREEMENT_START_DATE FECHAHORAALTA,F.AGREEMENT_END_DATE FECHABAJA,
                F.AGREEMENT_STATUS ESTADO_CONTRATO,
                T.FEC_INI_INCIDENCIA,T.FEC_FIN_INCIDENCIA,
                (SUBSTR(T.FEC_INI_INCIDENCIA,7,4)||SUBSTR(T.FEC_INI_INCIDENCIA,4,2)||SUBSTR(T.FEC_INI_INCIDENCIA,1,2))FECHA,
                (TO_DATE(T.FEC_FIN_INCIDENCIA, ''DD/MM/YYYY HH24:MI:SS'') - TO_DATE(T.FEC_INI_INCIDENCIA, ''DD/MM/YYYY HH24:MI:SS'')) * 24 * 60 MINUTOS_AFECTACION,
                T.NOMEST DPTO,T.NOMPVC PROVINCIA,T.NOMDST DISTRITO,T.IDPLANO,T.CODSUC,
                T.SECTOR,T.NUMSLC,T.FCHINI_INST,T.FCHFIN_INST, F.CUSTOMER_ACCOUNT_DESC CUSTCODE,F.AGREEMENT_CONTRACT_NUMBER CO_ID,
                F.CUSTOMER_FIRST_NAME||'' ''||F.CUSTOMER_LAST_NAME CONTACTO
                ,row_number() over(partition by T.NUMERO order by F.AGREEMENT_START_DATE desc) flag_1
                FROM DWA.DW_M_SUBSCRIPTION F
                JOIN (SELECT * FROM USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} WHERE CO_ID IS NOT NULL) T
                ON TO_CHAR(F.AGREEMENT_CONTRACT_NUMBER)= TO_CHAR(T.CO_ID)) WHERE flag_1=1';
                EXECUTE IMMEDIATE (STR);
        
                STR:='ALTER TABLE USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier} ADD (CARGO_FIJO NUMBER)';
                EXECUTE IMMEDIATE STR;
        
                STR:='ALTER TABLE USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier} ADD FLGCONSIDERA VARCHAR2(5)';
                EXECUTE IMMEDIATE STR;
            ELSE
                raise_application_error(-20001, 'La tabla USRAES.TMP_DEVOLUCION_MASIVO_{$this->userIdentifier} no tiene registros validos');
            END IF;

            STR := 'CREATE TABLE USRAES.TMP_SPCODE_BSCS_HIST_{$this->userIdentifier} NOLOGGING AS
            SELECT SPH.CO_ID, SPH.SNCODE, SPH.SPCODE,TE.FECHA
            FROM dws.sa_pr_serv_spcode_hist SPH
            JOIN USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier} TE ON SPH.CO_ID=TE.CO_ID
            WHERE SPH.SNCODE IN (SELECT SSH.SNCODE FROM dws.sa_PR_SERV_STATUS_HIST SSH  WHERE SSH.STATUS = ''A''
                    AND SSH.CO_ID = TE.CO_ID
                    AND SSH.HISTNO =(SELECT MAX(H.HISTNO) FROM dws.sa_PR_SERV_STATUS_HIST H WHERE H.CO_ID = SSH.CO_ID
                                    AND H.SNCODE = SSH.SNCODE
                                    AND H.VALID_FROM_DATE <=TO_DATE(TE.FECHA, ''YYYYMMDD'') + 1)
                    AND EXISTS (SELECT 1 FROM dws.sa_CONTRACT_ALL CA WHERE CA.CO_ID = SSH.CO_ID  AND CA.SCCODE = 6))
            AND SPH.HISTNO =  (SELECT MAX(SP.HISTNO) FROM dws.sa_pr_serv_spcode_hist SP WHERE SP.CO_ID = SPH.CO_ID
                                AND SP.SNCODE = SPH.SNCODE
                                AND SP.VALID_FROM_DATE <=  TO_DATE(TE.FECHA, ''YYYYMMDD'') + 1)';
            EXECUTE IMMEDIATE STR;

            STR:='CREATE TABLE USRAES.TABLA_DWH_{$this->userIdentifier} AS
            SELECT PS.CO_ID,PS.SNCODE,T.SPCODE,PS.ACCESSFEE, PS.OVW_ACC_PRD,PS.OVW_ACCESS,
                (SELECT RH.TMCODE FROM dws.sa_RATEPLAN_HIST RH
                WHERE RH.CO_ID = PS.CO_ID
                AND RH.SEQNO =(SELECT MAX(HJ.SEQNO) FROM dws.sa_RATEPLAN_HIST HJ
                                    WHERE HJ.CO_ID = RH.CO_ID
                                    AND HJ.TMCODE_DATE <= TO_DATE(T.FECHA, ''YYYYMMDD'') + 1)
                    ) TMCODE,
                    T.FECHA
            FROM dws.sa_PROFILE_SERVICE PS   JOIN
                USRAES.TMP_SPCODE_BSCS_HIST_{$this->userIdentifier} T ON PS.CO_ID = T.CO_ID
            AND PS.SNCODE = T.SNCODE';
            EXECUTE IMMEDIATE STR;

            STR:='CREATE TABLE USRAES.TMP_BSCS_CF_{$this->userIdentifier} NOLOGGING AS
            select /*+ PARALLEL(16)*/ co_id,sncode,spcode,accessfee,ovw_acc_prd,ovw_access,tmcode,fecha,costo from (
                SELECT DH.*,DECODE(DH.OVW_ACC_PRD,0,TMB.ACCESSFEE,-2,TMB.ACCESSFEE,DECODE(DH.OVW_ACCESS,NULL,
                TMB.ACCESSFEE, ''R'',NVL((TMB.ACCESSFEE * DH.ACCESSFEE),0),NVL(DH.ACCESSFEE,0))) COSTO
                FROM USRAES.TABLA_DWH_{$this->userIdentifier} DH, dws.sa_MPULKTMB TMB
                WHERE DH.TMCODE = TMB.TMCODE
                AND DH.SNCODE = TMB.SNCODE
                AND DH.SPCODE = TMB.SPCODE
                AND TMB.VSCODE = (SELECT MAX(V.VSCODE) FROM dws.sa_RATEPLAN_VERSION V
                                WHERE V.TMCODE = TMB.TMCODE AND V.STATUS = ''P''
                                AND V.VSDATE <= TO_DATE(DH.FECHA,''YYYYMMDD'')+1)
            )
            group by co_id,sncode,spcode,accessfee,ovw_acc_prd,ovw_access,tmcode,fecha,costo';
            EXECUTE IMMEDIATE STR;
        END;"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}(
        TICKET                      VARCHAR2(100),
        CODCLI                      VARCHAR2(500),
        NOMCLI                      VARCHAR2(500),
        NRO_DOC                     VARCHAR2(500),
        TIPDOC                      VARCHAR2(500),
        NUMERO                      VARCHAR2(100),
        CID                         NUMBER,
        FAMILIA                     VARCHAR2(100),
        IDPLANO                     VARCHAR2(100),
        FEC_INI_INCIDENCIA          DATE,
        FEC_FIN_INCIDENCIA          DATE,
        MINUTOS_AFECTACION          NUMBER,
        DPTO                        VARCHAR2(100),
        PROVINCIA                   VARCHAR2(100),
        DISTRITO                    VARCHAR2(100),
        MONEDA                      VARCHAR2(100),
        CR_NETO                     NUMBER,
        FCHINI_INST                 DATE,
        FCHFIN_INST                 DATE,
        CICFAC_DEVOL                VARCHAR2(100),
        CUSTCODE                    VARCHAR2(500),
        CO_ID                       VARCHAR2(500),
        FECHAALTA                   DATE,
        ESTADO_CONTRATO             VARCHAR2(250),
        CUSTOMER_ID                 VARCHAR2(250),
        FUENTE                      VARCHAR2(100),
        CODSRV                      VARCHAR2(50),
        DSCSRV                      VARCHAR2(50),
        OBS                         VARCHAR2(100),
        ESTADO_IDINTPROD_DEVOL      VARCHAR2(100),
        SERVICIO_DEVOL              VARCHAR2(100),
        IDINTPROD_DEVOL             NUMBER,
        TASA_AL                     DATE,
        FCH_CALC_TASA               DATE,
        TASA                        NUMBER,
        MONEDA_DEVOL                VARCHAR2(100),
        MONTO_DEVOL_CIGV            NUMBER,
        INTERES                     NUMBER,
        MONTO_PRINC_CIGV            NUMBER,
        ULT_FECHA                   NUMBER,
        CR_NETOCIGV                 NUMBER,
        MONTO_PRINCIPAL             NUMBER
        )"];

        $queries[] = ["sql" => "BEGIN
            MERGE INTO USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier} A
            USING (SELECT C.CO_ID,SUM(C.COSTO)COSTO FROM USRAES.TMP_BSCS_CF_{$this->userIdentifier} C,dws.sa_MPUSPTAB M
            WHERE C.SPCODE=M.SPCODE AND C.COSTO>0
            GROUP BY C.CO_ID)B
            ON (A.CO_ID=B.CO_ID)
            WHEN MATCHED THEN
            UPDATE SET A.CARGO_FIJO= B.COSTO;
            commit;

            INSERT INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} (TICKET,CODCLI,NOMCLI,NRO_DOC,TIPDOC,NUMERO,CID,FAMILIA,
            IDPLANO,FEC_INI_INCIDENCIA,FEC_FIN_INCIDENCIA,MINUTOS_AFECTACION,DPTO,PROVINCIA,DISTRITO,MONEDA,CR_NETO,FCHINI_INST,FCHFIN_INST,
            CICFAC_DEVOL,CUSTCODE,CO_ID,FECHAALTA,ESTADO_CONTRATO,CUSTOMER_ID,FUENTE)
            (SELECT /*SQ_CODTICKET_MASIV.NEXTVAL,T.TRIMESTRE,*/T.TICKET,T.CODCLI,T.RAZON_SOCIAL,T.NRO_DOCUMENTO,T.TIPDOC_CLIENTE,T.NUMERO,T.CID,T.FAMILIA,
            T.IDPLANO,TO_DATE(T.FEC_INI_INCIDENCIA,'DD/MM/YYYY HH24:MI:SS'),TO_DATE(T.FEC_FIN_INCIDENCIA,'DD/MM/YYYY HH24:MI:SS'),T.MINUTOS_AFECTACION,
            T.DPTO,T.PROVINCIA,T.DISTRITO,'SOLES',T.CARGO_FIJO,T.FCHINI_INST,T.FCHFIN_INST,T.CICLO,T.CUSTCODE,T.CO_ID,T.FECHAHORAALTA,T.ESTADO_CONTRATO,
            T.CUSTOMER_ID,'BSCS'
            FROM USRAES.WRK_DEV_INC_MASI_BSCS_{$this->userIdentifier} T
            WHERE  T.FLGCONSIDERA IS NULL AND T.CARGO_FIJO > 0 AND T.CARGO_FIJO IS NOT NULL);
            COMMIT;
        END;"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} ADD (BANWID NUMBER,CR_NETO NUMBER,ULT_FECHA NUMBER)"];

        $queries[] = ["sql" => "DECLARE
            V_TIPSRV VARCHAR2(5);
            V_CR_NETO NUMBER;
            V_COUNT NUMBER;

            cursor CUR_WRK_DEVOL_INC_MASIV is
            select rowid rrowid, IDINSTPROD,MONTOCR_PROD FROM USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier};

            function f_calcula_tarifa_desc_temp(
                ln_idinstprod dws.sa_instxproducto.idinstprod%type,
                ln_montocr dws.sa_instxproducto.montocr%type
                --ln_monto_out out dws.sa_instxproducto.montocr%type
            ) RETURN dws.sa_instxproducto.montocr%type
            is
                ln_monto_out dws.sa_instxproducto.montocr%type;
            
                cursor cur_prom is
                select a.idinstprom,
                a.idinstprod,
                b.porcentaje,
                b.idprom
                from dws.sa_instanciaxprom a,
                dws.sa_promocion b
                where a.idprom = b.idprom and
                b.afectacr = 1 and a.estado != 9 and
                (b.limiteaplic = 0 or b.limiteaplic is null) and
                a.idinstprod = ln_idinstprod;
            
                ln_count number;
                n_porcent number;
                ln_calmot dws.sa_instxproducto.montocr%type;
            begin
                select count(*) into ln_count
                from dws.sa_instanciaxprom a,
                dws.sa_promocion b
                where a.idprom = b.idprom and
                b.afectacr = 1 and a.estado != 9 and
                (b.limiteaplic = 0 or b.limiteaplic is null) and
                a.idinstprod = ln_idinstprod;
            
                if ln_count = 0 then
                    ln_monto_out := ln_montocr;
                else
                    ln_calmot := ln_montocr;
                    for c_prom in cur_prom loop
                        n_porcent := 1;
                        n_porcent := (100 - c_prom.porcentaje) * n_porcent / 100;
                        ln_monto_out := ln_calmot * n_porcent;
                        ln_calmot := ln_monto_out;
                    end loop;
                end if;
                return ln_monto_out;
            end;
        BEGIN
            SELECT COUNT(*) INTO V_COUNT FROM USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier};
            IF V_COUNT > 0 THEN
                SELECT DISTINCT I.TIPSRV INTO V_TIPSRV FROM USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} I ;
            
                IF (V_TIPSRV = '0006' OR V_TIPSRV = '0054') THEN
                    UPDATE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} W
                    SET BANWID = (SELECT DISTINCT  TY.BANWID FROM dws.sa_tystabsrv TY
                        WHERE TY.CODSRV = W.CODSRV
                        AND TY.TIPSRV = W.TIPSRV
                        AND TY.TIPSRV = '0006')
                    WHERE EXISTS (SELECT 1 FROM dws.sa_tystabsrv TY
                        WHERE TY.CODSRV = W.CODSRV
                        AND TY.TIPSRV = W.TIPSRV
                        AND TY.TIPSRV = '0006');
                    COMMIT;
                END IF;
            END IF;

            UPDATE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} A
            SET --CR_NETO = usraes.f_calcula_tarifa_desc_temp (IDINSTPROD,MONTOCR_PROD),
            ULT_FECHA = TO_NUMBER(TO_CHAR(LAST_DAY(TO_DATE(SUBSTR(FEC_INI_INCIDENCIA, 1, 10),'DD/MM/YYYY')),'DD'));
            COMMIT;

            FOR V_ROW IN CUR_WRK_DEVOL_INC_MASIV LOOP
                V_CR_NETO := f_calcula_tarifa_desc_temp (V_ROW.IDINSTPROD, V_ROW.MONTOCR_PROD);
                
                UPDATE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} A SET
                a.CR_NETO = V_CR_NETO
                WHERE rowid = V_ROW.rrowid;
                commit;
            END LOOP;
        END;"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} ADD FLGCONSIDERA VARCHAR2(5)"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} (TICKET,CODCLI,NOMCLI,NRO_DOC,TIPDOC,NUMERO,CID,FAMILIA,CODSRV,DSCSRV,
            IDPLANO,FEC_INI_INCIDENCIA,FEC_FIN_INCIDENCIA,MINUTOS_AFECTACION,DPTO,PROVINCIA,DISTRITO,MONEDA,CR_NETO,FCHINI_INST,FCHFIN_INST,FUENTE)
            (SELECT /*SQ_CODTICKET_MASIV.NEXTVAL, T.TRIMESTRE,*/T.TICKET,T.CODCLI,T.NOMCLI,T.NRO_DOC,T.TIPDOC,T.NUMERO,T.CID,T.FAMILIA,T.CODSRV,T.DSCSRV,T.IDPLANO,
            TO_DATE(T.FEC_INI_INCIDENCIA,'DD/MM/YYYY HH24:MI:SS'),TO_DATE(T.FEC_FIN_INCIDENCIA,'DD/MM/YYYY HH24:MI:SS'),T.MINUTOS_AFECTACION,
            T.DPTO,T.PROVINCIA,T.DISTRITO,T.MONEDA,T.CR_NETO,T.FECINI_INSPROD,T.FECFIN_INSPROD,'SGA'
            FROM USRAES.WRK_DEVOL_INC_MASIV_{$this->userIdentifier} T
            WHERE T.FLGCONSIDERA IS NULL
            AND T.CR_NETO > 0
            AND T.CR_NETO IS NOT NULL);
            COMMIT;
        END;"];

        // PARTE 2

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} NOLOGGING PARALLEL 8 AS
        SELECT W.TICKET,W.CODCLI,W.NOMCLI,W.TIPDOC,W.NRO_DOC,W.CID,W.NUMERO,I.TIPSRV,W.FAMILIA,W.DSCSRV,W.CODSRV,W.IDPLANO,W.FEC_INI_INCIDENCIA,
        W.FEC_FIN_INCIDENCIA,W.MINUTOS_AFECTACION,W.DPTO,W.PROVINCIA,W.FCHINI_INST,W.FCHFIN_INST,W.DISTRITO,W.MONEDA,W.CR_NETO
        FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} W,(SELECT TIPSRV,DSCTIPSRV FROM USRAES.SA_DEVOLUCION_EQUIVALENCIAS) I
        WHERE W.TICKET ='{$ticket}' AND TRIM(W.FAMILIA)=TRIM(I.DSCTIPSRV)"];
        
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_DEVINSTXPROD_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_DEVINSTXPROD_{$this->userIdentifier} NOLOGGING PARALLEL 4
        AS
        SELECT S.CODCLI,S.ESTINSSRV,S.CODINSSRV,
               I.MONTOCR,I.IDMONEDACR,I.IDINSTPROD,
               I.ESTADO,I.FECINI,I.FECFIN,
               I.CICFAC,I.DESCRIPCION DSC_PRODUCTO,
               I.IDPRODUCTO,I.PID
        FROM dws.sa_inssrv S
        JOIN USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W ON S.CODCLI = W.CODCLI
        JOIN dws.sa_instxproducto I ON W.CODCLI = I.CODCLI
        WHERE S.ESTINSSRV = 1
           AND I.MONTOCR > 0
           AND I.ESTADO = 1
           AND I.FECFIN IS NULL
           AND I.CICFAC IN (11,21,26,36,37,3,29,6,12,20,23)
           AND I.idproducto NOT IN ( 771,502,702,725,689,4,745,721,501)
           AND I.IDMONEDACR=1"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier} NOLOGGING PARALLEL 4
        AS SELECT I.CODCLI,P.PID,P.DESCRIPCION,I.ESTINSSRV,P.ESTINSPRD,P.CODINSSRV,
            P.FECINI FECINI_INSPRD,P.FECFIN FECFIN_INSPRD,
            P.NUMSLC,TY.DSCSRV,P.CODSRV,
            I.MONTOCR,I.IDMONEDACR,I.IDINSTPROD,I.ESTADO,
            I.FECINI,I.FECFIN,I.CICFAC,I.DSC_PRODUCTO,I.IDPRODUCTO
        FROM USRAES.TMP_DEVINSTXPROD_{$this->userIdentifier} I JOIN
            dws.sa_insprd P ON I.CODINSSRV=P.CODINSSRV AND I.PID=P.PID
        LEFT JOIN dws.sa_tystabsrv TY ON P.CODSRV=TY.CODSRV
        WHERE TY.DSCSRV NOT LIKE '%Alquiler%'
        AND P.estinsprd =1"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier} ADD (CR_NETO NUMBER)"];
        $queries[] = ["sql" => "CREATE INDEX IDX_USRAES_TMP_SA_DEVOL_CASO1_{$this->userIdentifier}
        ON USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier}(CODCLI,IDINSTPROD)"];
         
        $queries[] = ["sql" => "DECLARE
            V_CR_NETO NUMBER;

            CURSOR CUR_TMP_SA_DEVOL_CASO1 IS
            SELECT ROWID RROWID, IDINSTPROD, MONTOCR FROM USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier}
            WHERE IDPRODUCTO NOT IN (888)
            AND CODSRV NOT IN ('4602', '8928', '8929', '6441')
            AND CR_NETO IS NULL;

            function f_calcula_tarifa_desc_temp(
                ln_idinstprod dws.sa_instxproducto.idinstprod%type,
                ln_montocr dws.sa_instxproducto.montocr%type
                --ln_monto_out out dws.sa_instxproducto.montocr%type
            ) RETURN dws.sa_instxproducto.montocr%type
            is
                ln_monto_out dws.sa_instxproducto.montocr%type;
            
                cursor cur_prom is
                select a.idinstprom,
                a.idinstprod,
                b.porcentaje,
                b.idprom
                from dws.sa_instanciaxprom a,
                dws.sa_promocion b
                where a.idprom = b.idprom and
                b.afectacr = 1 and a.estado != 9 and
                (b.limiteaplic = 0 or b.limiteaplic is null) and
                a.idinstprod = ln_idinstprod;
            
                ln_count number;
                n_porcent number;
                ln_calmot dws.sa_instxproducto.montocr%type;
            begin
                select count(*) into ln_count
                from dws.sa_instanciaxprom a,
                dws.sa_promocion b
                where a.idprom = b.idprom and
                b.afectacr = 1 and a.estado != 9 and
                (b.limiteaplic = 0 or b.limiteaplic is null) and
                a.idinstprod = ln_idinstprod;
            
                if ln_count = 0 then
                    ln_monto_out := ln_montocr;
                else
                    ln_calmot := ln_montocr;
                    for c_prom in cur_prom loop
                        n_porcent := 1;
                        n_porcent := (100 - c_prom.porcentaje) * n_porcent / 100;
                        ln_monto_out := ln_calmot * n_porcent;
                        ln_calmot := ln_monto_out;
                    end loop;
                end if;
                return ln_monto_out;
            end;
        BEGIN
            FOR V_ROW IN CUR_TMP_SA_DEVOL_CASO1 LOOP
                V_CR_NETO := f_calcula_tarifa_desc_temp (V_ROW.IDINSTPROD, V_ROW.MONTOCR);
                UPDATE USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier} A
                SET A.CR_NETO = V_CR_NETO
                WHERE ROWID = V_ROW.RROWID;
                COMMIT;
            END LOOP;        
        END;"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} ADD (
        BANWID NUMBER,
        ULT_FECHA NUMBER,
        MONTO_PRINCIPAL NUMBER,
        CR_NETOCIGV NUMBER,
        MONTO_PRINC_CIGV NUMBER,
        IDINTPROD_DEVOL NUMBER(10),
        CICFAC_DEVOL NUMBER(5),
        FECINI_DEVOL DATE,
        FECFIN_DEVOL DATE,
        ESTADO_IDINTPROD_DEVOL CHAR(1),
        TASA_AL DATE,
        FCH_CALC_TASA  DATE,
        INTERES  NUMBER,
        OBS  VARCHAR2(30),
        SERVICIO_DEVOL VARCHAR2(100),
        TASA  NUMBER)"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} ADD (
        FAC_ULTI_SBS NUMBER,
        FAC_NUEV_REFE NUMBER,
        FAC_ACUM_PROY NUMBER,
        FAC_ACUM_INI NUMBER)"];

        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET ULT_FECHA = TO_NUMBER(TO_CHAR(LAST_DAY(TRUNC(FEC_INI_INCIDENCIA, 'DD')),'DD'));
            COMMIT;
        END;"];

        $queries[] = ["sql" => "DECLARE
            V_TIPSRV VARCHAR2(5);
        BEGIN
            SELECT DISTINCT I.TIPSRV INTO V_TIPSRV FROM USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} I;
        
            IF (V_TIPSRV = '0006' OR V_TIPSRV = '0054') THEN
                UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
                SET BANWID = (SELECT DISTINCT  TY.BANWID FROM dws.sa_tystabsrv TY
                            WHERE TY.CODSRV = W.CODSRV
                            AND TY.TIPSRV = W.TIPSRV
                            AND TY.TIPSRV = '0006')
                WHERE EXISTS (SELECT 1 FROM dws.sa_tystabsrv TY
                            WHERE TY.CODSRV = W.CODSRV
                            AND TY.TIPSRV = W.TIPSRV
                            AND TY.TIPSRV = '0006');
                COMMIT;
            
            END IF;
        END;"];

        $queries[] = ["sql" => "DECLARE
            CURSOR X IS
            SELECT DISTINCT A.* FROM USRAES.TMP_SA_DEVOL_CASO1_{$this->userIdentifier} A
            WHERE A.CR_NETO>0
            AND A.CODSRV NOT IN ('4602','AALC','AERW','AAQD','AAQE','AALE','AAQF','AAQK');
        BEGIN
            FOR X1 IN X LOOP
                UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
                SET W.IDINTPROD_DEVOL  = X1.IDINSTPROD,
                W.CICFAC_DEVOL = X1.CICFAC,
                W.FECINI_DEVOL = X1.FECINI,
                W.FECFIN_DEVOL = X1.FECFIN,
                W.ESTADO_IDINTPROD_DEVOL = X1.ESTADO,
                W.OBS ='DEVOLVER_FACTURA',
                W.SERVICIO_DEVOL = X1.DSC_PRODUCTO
                WHERE  W.CODCLI = X1.CODCLI;
            
                COMMIT;
            END LOOP;
        END;"];

        $queries[] = ["sql" => "DECLARE
            TIPO_CAMBIO NUMBER := :p_tipo_cambio;
        BEGIN
            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.MONTO_PRINCIPAL = ROUND((W.MINUTOS_AFECTACION * (W.CR_NETO*TIPO_CAMBIO) / (24*60*W.ULT_FECHA) ),2)
            WHERE W.MONEDA <> 'SOLES';
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.MONTO_PRINCIPAL = ROUND((W.MINUTOS_AFECTACION * W.CR_NETO / (24*60*W.ULT_FECHA) ),2)
            WHERE  W.MONEDA='SOLES';
            COMMIT;
        END;", "params" => ["p_tipo_cambio" => $tipo_cambio]];

        $this->exec_sql($queries);

        $fechaIniDetalle = null;
        $factorAcumulado3 = 'null';
        $result = DB::select(DB::raw("SELECT TO_CHAR(T.FEC_INI_INCIDENCIA, 'DDMMYYYY') FECHA_INICIO
        FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} T
        WHERE T.TICKET = '{$ticket}'"));

        if(count($result)>0){
            $fechaIniDetalle = $result[0]->fecha_inicio;
        }

        $result = DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT FACTORACUMULADO FROM USRAES.DWH_TASAINTERES_SBS
        WHERE FECHA = TO_DATE('{$fechaIniDetalle}','DDMMYYYY')"));
        if(count($result)>0){
            $factorAcumulado3 = $result[0]->factoracumulado;
        }

        $queries = [];
        $queries[] = ["sql" => "DECLARE
            V_FECHA_INICIO VARCHAR2(15);
            V_FEC_ULT_FA_SBS VARCHAR2(8);
            V_FECHATASA VARCHAR2(15);
            VALOR_TASA NUMBER;
            TIPO_CAMBIO NUMBER;
        BEGIN
            /*
            SELECT TO_CHAR(T.FEC_INI_INCIDENCIA, 'DDMMYYYY')
            INTO V_FECHA_INICIO
            FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} T
            WHERE T.TICKET = '{$ticket}';
            
            SELECT TO_CHAR(TRUNC(SYSDATE - 1), 'DDMMYYYY')
            INTO V_FEC_ULT_FA_SBS
            FROM DUAL;
            */

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET FAC_ULTI_SBS = {$factorAcumulado};--Fecha del último FA publicado por SBS
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET FAC_NUEV_REFE = {$factorAcumulado2};
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET FAC_ACUM_PROY =  (FAC_ULTI_SBS  * ( FAC_ULTI_SBS  / FAC_NUEV_REFE));
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET FAC_ACUM_INI = {$factorAcumulado3};
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET TASA =  decode(ROUND( FAC_ACUM_PROY/FAC_ACUM_INI - 1 ,2),0,0.01,ROUND( FAC_ACUM_PROY/FAC_ACUM_INI - 1 ,2)),
                    FCH_CALC_TASA = TO_DATE('{$strFechaDevolucion}', 'YYYYMMDD');
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.CR_NETOCIGV = ROUND((W.CR_NETO * TIPO_CAMBIO) * 1.18,2)
            WHERE W.MONEDA <> 'SOLES';
            COMMIT;
        END;"];

        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.CR_NETOCIGV = ROUND(W.CR_NETO * 1.18,2)
            WHERE  W.MONEDA = 'SOLES';
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.TASA_AL=(SELECT TRUNC(SYSDATE-1) FROM DUAL);
            COMMIT;

            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.MONTO_PRINC_CIGV = ROUND((W.MINUTOS_AFECTACION * W.CR_NETOCIGV/ (24*60*W.ULT_FECHA) ),2);
            COMMIT;
        END;"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} ADD (MONTO_DEVOL_CIGV NUMBER)"];
        
        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.INTERES=decode(round( ((W.MONTO_PRINC_CIGV * W.FAC_ACUM_PROY)/W.FAC_ACUM_INI ) - W.MONTO_PRINC_CIGV,2),0,0.01,round( ((W.MONTO_PRINC_CIGV * W.FAC_ACUM_PROY)/W.FAC_ACUM_INI ) - W.MONTO_PRINC_CIGV,2));
            COMMIT;
            
            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier} W
            SET W.MONTO_DEVOL_CIGV = ROUND(( W.INTERES + W.MONTO_PRINC_CIGV),2);
            COMMIT;
            
            UPDATE USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier}
            SET OBS='VERIFICAR_DEUDA'
            WHERE OBS IS NULL;
            COMMIT;
        END;"];

        $queries[] = ["sql" => "DECLARE
            CURSOR C1 IS
            SELECT * FROM USRAES.WRK_DEVOLUCION_MASIVA_{$this->userIdentifier};
        BEGIN
            FOR X IN C1
            LOOP
                UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} W
                SET W.MONTO_PRINCIPAL=X.MONTO_PRINCIPAL,
                W.CR_NETOCIGV=X.CR_NETOCIGV,
                W.ULT_FECHA=X.ULT_FECHA,
                W.MONTO_PRINC_CIGV=X.MONTO_PRINC_CIGV,
                W.INTERES=X.INTERES,
                W.MONTO_DEVOL_CIGV=X.MONTO_DEVOL_CIGV,
                W.MONEDA_DEVOL='SOLES',
                W.TASA=X.TASA,
                W.FCH_CALC_TASA=X.FCH_CALC_TASA,
                W.TASA_AL=X.TASA_AL,
                W.IDINTPROD_DEVOL=X.IDINTPROD_DEVOL,
                W.CICFAC_DEVOL=X.CICFAC_DEVOL,
                W.SERVICIO_DEVOL=X.SERVICIO_DEVOL,
                W.ESTADO_IDINTPROD_DEVOL=X.ESTADO_IDINTPROD_DEVOL,
                W.OBS=X.OBS
                WHERE W.TICKET='{$ticket}'
                AND (W.CID=X.CID OR W.NUMERO=X.NUMERO)
                ;
                COMMIT;
            END LOOP;
        END;"];

        $queries[] = ["sql" => "DECLARE
            STR VARCHAR2(4000);
            S_PART VARCHAR2(8);
            v_ticket VARCHAR2(100) := :p_ticket;
        BEGIN
            MERGE INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}  A
            USING (select AGREEMENT_CONTRACT_NUMBER,CUSTOMER_ACCOUNT_BILLING_CYCLE_SC from DWA.DW_M_SUBSCRIPTION where AGREEMENT_CONTRACT_NUMBER
            in (select CO_ID from USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} where ticket= v_ticket group by CO_ID)
            group by AGREEMENT_CONTRACT_NUMBER,CUSTOMER_ACCOUNT_BILLING_CYCLE_SC) T
            ON (A.CO_ID=T.AGREEMENT_CONTRACT_NUMBER)
            WHEN MATCHED THEN
            UPDATE SET A.CICFAC_DEVOL= T.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC
            WHERE A.TICKET= v_ticket AND A.FUENTE='BSCS';
            COMMIT;

            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} w
            SET w.obs='DEVOLVER_FACTURA_BSCS'
            WHERE w.ticket= v_ticket
            AND w.estado_contrato in ('A','S')
            AND w.fuente='BSCS';
            COMMIT;

            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} w
            SET w.obs='VERIFICAR_DEUDA_BSCS'
            WHERE w.ticket = v_ticket
            AND w.estado_contrato='D'
            AND w.fuente='BSCS';
            COMMIT;

            --DELETE FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
            DELETE FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}
            WHERE TICKET = v_ticket AND NRO_DOC IN 
            (SELECT RUC FROM USRAES.CUENTA_DE_GOBIERNO_TMP GROUP BY RUC);
            COMMIT;
        END;", "params" => ["p_ticket" => $ticket]];

        /*$queries[] = ["sql" => "BEGIN
            DELETE FROM USRAES.INPUT_DEVO_FIJA
            WHERE (TICKET) IN (
                SELECT TICKET FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET
            );
            COMMIT;

            DELETE FROM USRAES.input_devo_fija_plano
            WHERE (TICKET) IN (
                SELECT TICKET FROM USRAES.input_devo_fija_plano_{$this->userIdentifier} GROUP BY TICKET
            );
            COMMIT;

            INSERT INTO USRAES.INPUT_DEVO_FIJA(
                TICKET,DEPARTAMENTO,PROVINCIA,
                DISTRITO,SERVICIO_AFECTADO,FECHA_INI,FECHA_FIN,MESES
            )
            SELECT
            TICKET,DEPARTAMENTO,PROVINCIA,
            DISTRITO,SERVICIO_AFECTADO,FECHA_INI,FECHA_FIN,MESES
            FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier};
            COMMIT;

            insert into USRAES.input_devo_fija_plano(ticket, departamento, provincia, distrito, plano)
            SELECT ticket, departamento, provincia, distrito, plano FROM USRAES.input_devo_fija_plano_{$this->userIdentifier};
            commit;
        END;"];*/

        $this->exec_sql($queries);

        $codClientes = DB::connection($this->connection)
        ->select(DB::raw("SELECT to_number(CUSTOMER_ID) as customer_id
        FROM (
            SELECT
            a.*,
            row_number() over(partition by ticket,CODCLI order by cr_neto desc) flag
            FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} a
        ) WHERE flag=1"));

        $this->chDb->write("DROP TABLE IF EXISTS default.base_ext_cod_cli_{$this->userIdentifier}");
        $this->chDb->write("CREATE TABLE default.base_ext_cod_cli_{$this->userIdentifier}(
        customer_id UInt64
        )
        ENGINE = MergeTree
        PRIMARY KEY customer_id
        SETTINGS index_granularity = 8192");

        $data = [];
        foreach($codClientes as $row){
            $data[] = [$row->customer_id];
        }
        $codClientes = [];
        $this->chDb->insert("default.base_ext_cod_cli_{$this->userIdentifier}", $data, ["customer_id"]);

        $data = [];

        try {
            $strFechaIniDay = $fechaIni->format("Ymd");

            $queryClickHouse = "SELECT
                -- X.hourpolled,
                -- X.serialnumber,
                -- X.ifoutoctets,
                Y.codigo_cliente as customer_id
                -- Y.mac_cm,
                -- Y.tipo,
                -- Y.fecha
            FROM
            (
                SELECT
                    hourpolled,
                    serialnumber,
                    ifoutoctets
                FROM hfc.ftth{$strFechaIniDay}
                UNION ALL
                SELECT
                    hourpolled,
                    macaddress AS serialnumber,
                    traffic AS ifoutoctets
                FROM hfc.hfc{$strFechaIniDay}
            ) AS X
            INNER JOIN hfc.clientes_hfc_ftth_hist AS Y ON upper(X.serialnumber) = Y.mac_cm
            WHERE (Y.codigo_cliente in (
                select z.customer_id from default.base_ext_cod_cli_{$this->userIdentifier} z
            ))
            AND (hourpolled < formatDateTime(parseDateTimeBestEffort('{$strFechaIniF1}'), '%H:%i:%s'))
            AND ((hourpolled >= formatDateTime(parseDateTimeBestEffort('{$strFechaIniF1}') - toIntervalMinute(15), '%H:%i:%s'))
            AND (hourpolled <= formatDateTime(parseDateTimeBestEffort('{$strFechaIniF1}') + toIntervalMinute(15), '%H:%i:%s') ))
            and X.ifoutoctets > 167772160
            GROUP BY 1";

            $data = $this->chDb->select($queryClickHouse)->rows();
        } catch (\Throwable $th) {
            $data = [];
        }

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.base_ext_cod_cli_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.base_ext_cod_cli_{$this->userIdentifier}(
            customer_id  VARCHAR2(100)
        )"];

        $this->exec_sql($queries);
        
        $chunk = [];
        $lastIndex = count($data)-1;
        foreach($data as $index => $row){
            $chunk[] = ["customer_id" => $row['customer_id']];
            if(count($chunk) === 20 || $index === $lastIndex){
                DB::connection($this->connection)->table("usraes.base_ext_cod_cli_{$this->userIdentifier}")->insert($chunk);
                $chunk = [];
            }
        }

        DB::connection($this->connection)
        ->statement("BEGIN
            DELETE FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}
            WHERE to_number(CICFAC_DEVOL) in (
                29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20
            );
            COMMIT;
        END;");

        $queries = [];
        $queries[] = ["sql" => "ALTER TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} ADD MODO_CONTRATACION VARCHAR2(100)"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} ADD TIPO_CLIENTE VARCHAR2(100)"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} ADD MSISDN_DEVOLVER VARCHAR2(100)"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} ADD COMENTARIOS VARCHAR2(100)"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} ADD FECHA_BAJA DATE"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_BASE_FIJA_DESACTIVOS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_BASE_FIJA_DESACTIVOS_{$this->userIdentifier}(
            TICKET VARCHAR2(500),
            CODCLI VARCHAR2(1000),
            NOMCLI VARCHAR2(1000),
            NRO_DOC VARCHAR2(1000),
            TIPDOC VARCHAR2(1000),
            NUMERO VARCHAR2(500),
            CID NUMBER,
            FAMILIA VARCHAR2(500),
            IDPLANO VARCHAR2(500),
            FEC_INI_INCIDENCIA DATE,
            FEC_FIN_INCIDENCIA DATE,
            MINUTOS_AFECTACION NUMBER,
            DPTO VARCHAR2(500),
            PROVINCIA VARCHAR2(500),
            DISTRITO VARCHAR2(500),
            MONEDA VARCHAR2(500),
            CR_NETO NUMBER,
            FCHINI_INST DATE,
            FCHFIN_INST DATE,
            CICFAC_DEVOL VARCHAR2(10 CHAR),
            CUSTCODE VARCHAR2(255 CHAR),
            CO_ID VARCHAR2(50),
            FECHAALTA DATE,
            ESTADO_CONTRATO VARCHAR2(20),
            CUSTOMER_ID VARCHAR2(50 CHAR),
            FUENTE VARCHAR2(50),
            CODSRV VARCHAR2(250),
            DSCSRV VARCHAR2(250),
            OBS VARCHAR2(500),
            ESTADO_IDINTPROD_DEVOL VARCHAR2(500),
            SERVICIO_DEVOL VARCHAR2(500),
            IDINTPROD_DEVOL NUMBER,
            TASA_AL DATE,
            FCH_CALC_TASA DATE,
            TASA NUMBER,
            MONEDA_DEVOL VARCHAR2(500),
            MONTO_DEVOL_CIGV NUMBER,
            INTERES NUMBER,
            MONTO_PRINC_CIGV NUMBER,
            ULT_FECHA NUMBER,
            CR_NETOCIGV NUMBER,
            MONTO_PRINCIPAL NUMBER,
            /*MTO_DEV_FACTURACION NUMBER,
            MTO_DIF_FACTURACION NUMBER,
            FACTURA_APLICADA VARCHAR2(100),
            FECHA_DEVOLUCION DATE,
            FECHA_REGISTRO_DEVOLUCION DATE,
            OBSERVACION VARCHAR2(250),*/
            FECHA_BAJA DATE,
            MODO_CONTRATACION VARCHAR2(20),
            TIPO_CLIENTE VARCHAR2(500),
            MSISDN_DEVOLVER VARCHAR2(20),
            COMENTARIOS VARCHAR2(28)
        )"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.TMP_BASE_FIJA_DESACTIVOS_{$this->userIdentifier}(
                TICKET, CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA, IDPLANO, FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION,
                DPTO, PROVINCIA, DISTRITO, MONEDA, CR_NETO, FCHINI_INST, FCHFIN_INST, CICFAC_DEVOL, CUSTCODE, CO_ID, FECHAALTA, ESTADO_CONTRATO, CUSTOMER_ID,
                FUENTE, CODSRV, DSCSRV, OBS, ESTADO_IDINTPROD_DEVOL, SERVICIO_DEVOL, IDINTPROD_DEVOL, TASA_AL, FCH_CALC_TASA, TASA, MONEDA_DEVOL, MONTO_DEVOL_CIGV,
                INTERES, MONTO_PRINC_CIGV, ULT_FECHA, CR_NETOCIGV, MONTO_PRINCIPAL,
                -- MTO_DEV_FACTURACION, MTO_DIF_FACTURACION, FACTURA_APLICADA, FECHA_DEVOLUCION, FECHA_REGISTRO_DEVOLUCION, OBSERVACION,
                FECHA_BAJA, MODO_CONTRATACION, TIPO_CLIENTE, MSISDN_DEVOLVER, COMENTARIOS
            )
            SELECT TICKET,
            CODCLI,
            NOMCLI,
            NRO_DOC,
            TIPDOC,
            NUMERO,
            CID,
            FAMILIA,
            IDPLANO,
            FEC_INI_INCIDENCIA,
            FEC_FIN_INCIDENCIA,
            MINUTOS_AFECTACION,
            DPTO,
            PROVINCIA,
            DISTRITO,
            MONEDA,
            CR_NETO,
            FCHINI_INST,
            FCHFIN_INST,
            CICLO CICFAC_DEVOL,
            MOVIL_CUSTCODE CUSTCODE,
            MOVIL_CO_ID CO_ID,
            FECHAALTA,
            MOVIL_STATUS ESTADO_CONTRATO,
            MOVIL_CUSTOMER_ID CUSTOMER_ID,
            SUBSCRIPTION_SOURCE_SYSTEM_DESC FUENTE,
            CODSRV,
            DSCSRV,
            OBS,
            ESTADO_IDINTPROD_DEVOL,
            SERVICIO_DEVOL,
            IDINTPROD_DEVOL,
            TASA_AL,
            FCH_CALC_TASA,
            TASA,
            MONEDA_DEVOL,
            MONTO_DEVOL_CIGV,
            INTERES,
            MONTO_PRINC_CIGV,
            ULT_FECHA,
            CR_NETOCIGV,
            MONTO_PRINCIPAL,
            CASE WHEN MOVIL_STATUS IN ('A','G') THEN NULL WHEN (MOVIL_STATUS NOT IN ('A','G') AND AGREEMENT_END_DATE<FCHFIN_INST) OR TRUNC(AGREEMENT_END_DATE)=TO_DATE('31/12/9999','DD/MM/YYYY') OR AGREEMENT_END_DATE IS NULL THEN FCHFIN_INST ELSE AGREEMENT_END_DATE END FECHA_BAJA,
            AGREEMENT_MODE MODO_CONTRATACION,
            TIPO_CLIENTE TIPO_CLIENTE,
            LINEA_ACTUAL MSISDN_DEVOLVER,
            CASE WHEN MOVIL_STATUS NOT IN ('A','G') OR AGREEMENT_END_DATE IS NULL THEN 'DEVOLUCION WEB' 
            WHEN AGREEMENT_MODE='POSTPAGO' THEN 'DEVOLUCION APLICADA-POSTPAGO' 
            WHEN AGREEMENT_MODE='PREPAGO' THEN 'DEVOLUCION APLICADA-PREPAGO' 
            END COMENTARIOS
            FROM (
            SELECT /*+ PARALLEL(20)*/
                TK.TICKET,TK.CODCLI,TK.NOMCLI,TK.NRO_DOC,TK.TIPDOC,TK.NUMERO,TK.CID,TK.FAMILIA,TK.IDPLANO,TK.FEC_INI_INCIDENCIA,TK.FEC_FIN_INCIDENCIA,
                TK.MINUTOS_AFECTACION,TK.DPTO,TK.PROVINCIA,TK.DISTRITO,TK.MONEDA,TK.CR_NETO,TK.FCHINI_INST,TK.FCHFIN_INST,TK.CICFAC_DEVOL,TK.CUSTCODE,
                TK.CO_ID,TK.FECHAALTA,TK.ESTADO_CONTRATO,TK.CUSTOMER_ID,TK.FUENTE,TK.CODSRV,TK.DSCSRV,TK.OBS,TK.ESTADO_IDINTPROD_DEVOL,TK.SERVICIO_DEVOL,
                TK.IDINTPROD_DEVOL,TK.TASA_AL,TK.FCH_CALC_TASA,TK.TASA,TK.MONEDA_DEVOL,TK.MONTO_DEVOL_CIGV,TK.INTERES,TK.MONTO_PRINC_CIGV,TK.ULT_FECHA,
                TK.CR_NETOCIGV,TK.MONTO_PRINCIPAL,TK.FECHA_BAJA,TK.MODO_CONTRATACION,/*TK.TIPO_CLIENTE,*/TK.MSISDN_DEVOLVER,TK.COMENTARIOS,
                SBS.CUSTOMER_ACCOUNT_SC CLIENTE_ID,
                CASE WHEN ID_CARD_TYPE_VALUE='RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_FIRST_NAME END NOMBRES,
                CASE WHEN ID_CARD_TYPE_VALUE='RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_LAST_NAME END APELLIDOS,
                SBS.CUSTOMER_FULL_NAME,
                SBS.ID_CARD_VALUE,
                SBS.ID_CARD_TYPE_VALUE,
                UPPER(DECODE(SBS.AGREEMENT_MODE,'PREPAGO','CONSUMER',SBS.CUSTOMER_ACCOUNT_CATEGORY_DESC)) TIPO_CLIENTE,  
                SBS.SUBSCRIPTION_SOURCE_SYSTEM_DESC,
                SBS.AGREEMENT_PRODUCT_OFFERING_SC,
                SBS.AGREEMENT_PRODUCT_OFFERING_DESC,
                CASE WHEN SBS.AGREEMENT_STATUS IS NULL THEN 'D' ELSE SBS.AGREEMENT_STATUS END MOVIL_STATUS,
                DECODE(AGREEMENT_MODE,'POSTPAGO',SBS.CUSTOMER_ACCOUNT_SC)   MOVIL_CUSTOMER_ID,
                DECODE(AGREEMENT_MODE,'POSTPAGO',SBS.CUSTOMER_ACCOUNT_DESC) MOVIL_CUSTCODE,
                DECODE(AGREEMENT_MODE,'POSTPAGO',SBS.AGREEMENT_SOURCE_CODE) MOVIL_CO_ID,
                CASE WHEN SBS.AGREEMENT_MODE IS NULL THEN 'POSTPAGO' ELSE SBS.AGREEMENT_MODE END AGREEMENT_MODE,
                SBS.AGREEMENT_START_DATE,
                SBS.AGREEMENT_END_DATE,
                SBS.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO,
                SBS.SUBSCRIPTION_ACCESS_NUMBER LINEA_ACTUAL,
                ROW_NUMBER() OVER(PARTITION BY TK.NRO_DOC ORDER BY CASE WHEN SBS.AGREEMENT_STATUS='A' THEN 1 ELSE 0 END DESC, SBS.CUSTOMER_ACCOUNT_ID DESC) R
            FROM 
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}
                WHERE TICKET = :ticket
                AND ((CASE FUENTE
                    WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
                    WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
                    ELSE 0 END
                ) = 0
                OR to_number(CICFAC_DEVOL) in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20) OR ESTADO_CONTRATO NOT IN ('A','G'))
            ) TK
            LEFT JOIN DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$partitionActual}) SBS -- INGRESAR EL AÑO Y MES ACTUAL
            ON SBS.ID_CARD_VALUE=TK.NRO_DOC AND SBS.AGREEMENT_SERVICE_GROUP='MOVIL'
            LEFT JOIN DWA.DW_T_SUBSCRIPTION_NUMBER SN ON TK.FEC_INI_INCIDENCIA BETWEEN SN.START_DATE AND SN.END_DATE 
            AND SN.AGREEMENT_ID=SBS.AGREEMENT_ID
            -- WHERE SBS.ID_CARD_VALUE IS NOT NULL
            -- WHERE NRO_DOC='03615309'
            )
            WHERE R=1;
            COMMIT;
        END;", "params" => ["ticket" => $ticket]];

        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}
            SET COMENTARIOS='DEVOLUCION APLICADA-POSTPAGO', MODO_CONTRATACION='POSTPAGO'
            WHERE TICKET=:ticket;
            COMMIT;

            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}
            SET COMENTARIOS='DEVOLUCION WEB'
            WHERE ((CASE FUENTE
            WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
            WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
            ELSE 0 END
            ) = 0
            OR to_number(CICFAC_DEVOL) in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20) OR ESTADO_CONTRATO NOT IN ('A','G')) AND TICKET=:ticket;
            COMMIT;

            MERGE INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} DMD
            USING USRAES.TMP_BASE_FIJA_DESACTIVOS_{$this->userIdentifier} TMP
            ON(DMD.TICKET=TMP.TICKET AND DMD.NRO_DOC=TMP.NRO_DOC)
            WHEN MATCHED THEN
            UPDATE SET DMD.CICFAC_DEVOL=TMP.CICFAC_DEVOL,
                DMD.CUSTCODE=TMP.CUSTCODE,
                DMD.CO_ID=TMP.CO_ID,
                DMD.ESTADO_CONTRATO=TMP.ESTADO_CONTRATO,
                DMD.CUSTOMER_ID=TMP.CUSTOMER_ID,
                DMD.FUENTE=TMP.FUENTE,
                DMD.MODO_CONTRATACION=TMP.MODO_CONTRATACION,
                DMD.TIPO_CLIENTE=TMP.TIPO_CLIENTE,
                DMD.MSISDN_DEVOLVER=TMP.MSISDN_DEVOLVER,
                DMD.COMENTARIOS=TMP.COMENTARIOS,
                DMD.FECHA_BAJA=TMP.FECHA_BAJA
            WHERE DMD.TICKET in (:ticket) AND ((CASE DMD.FUENTE
            WHEN 'BSCS' THEN (CASE WHEN DMD.ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
            WHEN 'SGA' THEN (CASE WHEN DMD.CICFAC_DEVOL IS NOT NULL AND DMD.FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
            ELSE 0 END
            ) = 0
            OR to_number(DMD.CICFAC_DEVOL) in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20));
            COMMIT;
        END;", "params" => ["ticket" => $ticket]];

        $this->exec_sql($queries);

        $cantidadUsuarios = DB::connection($this->connection)
        ->select("SELECT
        count(distinct a.codcli) usuarios_activos,
        count(distinct case when b.customer_id is not null then a.codcli end) usuarios_con_trafico
        from USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} a
        left join USRAES.base_ext_cod_cli_{$this->userIdentifier} b
        on b.customer_id = a.customer_id
        where a.MONTO_PRINCIPAL is not null
        AND a.ESTADO_CONTRATO = 'A'
        AND (CASE a.FUENTE
            WHEN 'BSCS' THEN (CASE WHEN a.ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
            WHEN 'SGA' THEN (CASE WHEN a.CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
            ELSE 0 END
        ) = 1");

        return $cantidadUsuarios[0];
    }

    public function processEnd($ticket, int $gruposUsuario){
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            DELETE FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
            WHERE (TICKET) IN (
                SELECT TICKET FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET
            );
            COMMIT;
        END;"];

        if ($gruposUsuario === 2) {
            $queries[] = ["sql" => "BEGIN
                DELETE FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}
                WHERE customer_id not in (
                    select b.customer_id from USRAES.base_ext_cod_cli_{$this->userIdentifier} b
                );
                COMMIT;
            END;"];
        }
        
        $queries[] = ["sql" => "BEGIN

            INSERT INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST(
                TICKET, CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA, IDPLANO,
                FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION,
                DPTO, PROVINCIA, DISTRITO, MONEDA, CR_NETO, FCHINI_INST, FCHFIN_INST,
                CICFAC_DEVOL, CUSTCODE, CO_ID, FECHAALTA, ESTADO_CONTRATO, CUSTOMER_ID,
                FUENTE, CODSRV, DSCSRV, OBS, ESTADO_IDINTPROD_DEVOL, SERVICIO_DEVOL,
                IDINTPROD_DEVOL, TASA_AL, FCH_CALC_TASA, TASA, MONEDA_DEVOL, MONTO_DEVOL_CIGV,
                INTERES, MONTO_PRINC_CIGV, ULT_FECHA, CR_NETOCIGV, MONTO_PRINCIPAL,
                MODO_CONTRATACION, TIPO_CLIENTE, MSISDN_DEVOLVER, COMENTARIOS, FECHA_BAJA
            )
            SELECT
            TICKET, CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA, IDPLANO,
            FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION,
            DPTO, PROVINCIA, DISTRITO, MONEDA, CR_NETO, FCHINI_INST, FCHFIN_INST,
            CICFAC_DEVOL, CUSTCODE, CO_ID, FECHAALTA, ESTADO_CONTRATO, CUSTOMER_ID,
            FUENTE, CODSRV, DSCSRV, OBS, ESTADO_IDINTPROD_DEVOL, SERVICIO_DEVOL,
            IDINTPROD_DEVOL, TASA_AL, FCH_CALC_TASA, TASA, MONEDA_DEVOL, MONTO_DEVOL_CIGV,
            INTERES, MONTO_PRINC_CIGV, ULT_FECHA, CR_NETOCIGV, MONTO_PRINCIPAL,
            MODO_CONTRATACION, TIPO_CLIENTE, MSISDN_DEVOLVER, COMENTARIOS, FECHA_BAJA
            FROM (
                SELECT
                a.*,
                row_number() over(partition by ticket,CODCLI order by cr_neto desc) flag
                FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier} a
            ) WHERE flag=1;
            COMMIT;

            DELETE FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL
            WHERE (ticket) IN (
                SELECT TICKET FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET, DEPARTAMENTO
            );
            COMMIT;

            INSERT INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL(
                TICKET, DEPARTAMENTO, FECHA, SERVICIO_AFECTADO, ABONADOS_AFECTADOS, ACREDITADOS, NO_ACREDITADOS
            )
            SELECT ticket,max(DPTO) Departamento,sysdate fecha,familia Servicio_Afectado,
            count(1) abonados_afectados, 0 ACREDITADOS, COUNT(1) NO_ACREDITADOS
            from USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
            WHERE (ticket) IN (
                SELECT TICKET FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET, DEPARTAMENTO
            )
            AND MONTO_PRINCIPAL is not null
            AND ESTADO_CONTRATO = 'A'
            AND (CASE FUENTE
                WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
                WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
                ELSE 0 END
            ) = 1
            GROUP BY ticket,familia;
            COMMIT;
        END;"];

        /*$queries[] = ["sql" => "DECLARE
            CURSOR CUR_TICKETS IS
            SELECT TICKET, SERVICIO_AFECTADO FROM USRAES.INPUT_DEVO_FIJA_TMP_{$this->userIdentifier} GROUP BY TICKET, SERVICIO_AFECTADO;
        BEGIN
            FOR V_ROW IN CUR_TICKETS
            LOOP
                EXECTE
            END LOOP;
        END;"];*/
        
        $this->exec_sql($queries);

        return DB::select(DB::raw("SELECT TICKET, CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA,
        CODSRV, DSCSRV, IDPLANO, FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION,
        DPTO, PROVINCIA, DISTRITO,MONEDA, CR_NETO
        FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_{$this->userIdentifier}"));

        /*
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE usraes.ext_dev_fija_ubica_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE usraes.ext_dev_fija_planos_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE usraes.ext_dev_fija_ticket_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("create table usraes.ext_dev_fija_ubica_{$this->userIdentifier}(
            codigo_id varchar2(64),
            departamento varchar2(200),
            provincia varchar2(200),
            distrito varchar2(200)
        )");
        DB::statement("create table usraes.ext_dev_fija_planos_{$this->userIdentifier}(
            codigo_id varchar2(64),
            plano varchar2(200)
        )");
        DB::statement("create table usraes.ext_dev_fija_ticket_{$this->userIdentifier}(
            codigo_id varchar2(64),
            ticket varchar2(100),
            servicio_afectado varchar2(100),
            fecha_ini date,
            fecha_fin date,
            meses_interes number,
            fecha_interes date
        )");

        foreach($distritos as $i => $row){
            $id = (new DateTime())->format("Ymd.u");
            DB::table("usraes.ext_dev_fija_ubica_{$this->userIdentifier}")
            ->insert([
                "codigo_id" => $id,
                "departamento" => $row[0],
                "provincia" => $row[1],
                "distrito" => $row[2]
            ]);
            foreach($row[3] as $plano){
                DB::table("usraes.ext_dev_fija_planos_{$this->userIdentifier}")
                ->insert([
                    "codigo_id" => $id,
                    "plano" => $plano,
                ]);
            }
        }

        $fechaInteres = new DateTime();
        $fechaInteres->modify("+{$mesesInteres} month");
        $fechaInteres = $fechaInteres->format("Y-m-d")." 00:00:00";

        foreach($tickets as $i => $row){
            $id = (new DateTime())->format("Ymd.u");
            DB::table("usraes.ext_dev_fija_ticket_{$this->userIdentifier}")
            ->insert([
                "codigo_id" => $id,
                "ticket" => $row[0],
                "servicio_afectado" => $row[1],
                "fecha_ini" => $row[2],
                "fecha_fin" => $row[3],
                "meses_interes" => $mesesInteres,
                "fecha_interes" => $fechaInteres,
            ]);
        }

        DB::statement("begin
            insert into usraes.ext_dev_fija_ubica(codigo_id, departamento, provincia, distrito)
            select codigo_id, departamento, provincia, distrito from usraes.ext_dev_fija_ubica_{$this->userIdentifier};
            commit;

            insert into usraes.ext_dev_fija_planos(codigo_id, plano)
            select codigo_id, plano from usraes.ext_dev_fija_planos_{$this->userIdentifier};
            commit;

            insert into usraes.ext_dev_fija_ticket(codigo_id, ticket, servicio_afectado, fecha_ini, fecha_fin, meses_interes, fecha_interes)
            select
            codigo_id, ticket, servicio_afectado, fecha_ini, fecha_fin, meses_interes, fecha_interes
            from usraes.ext_dev_fija_ticket_{$this->userIdentifier};
            commit;
        end;");*/
    }

    public function countReportByTicketDepartamento($ticket){
        $sql = "SELECT COUNT(1) counter FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
        WHERE TICKET= :p_ticket";
        $params = ["p_ticket" => $ticket];
        $data = DB::connection($this->connection)->select(DB::raw($sql), $params);
        return $data[0]->counter;
    }

    public function findInputsByNumReporteAndTicket($numReporte, $ticket)
    {
        $data = DB::connection($this->connection)->table("USRAES.INPUT_DEVO_FIJA")
        ->where("numero_reporte", $numReporte)
        ->where("ticket", $ticket)
        ->first();

        
        if($data !== null){
            $departamentos = DB::connection($this->connection)
            ->table("usraes.input_devo_fija_plano")
            ->select("departamento", "provincia", "distrito")
            ->where("numero_reporte", $numReporte)
            ->groupBy("departamento", "provincia", "distrito")
            ->get();
    
            foreach($departamentos as $row){
                $row->planos = DB::connection($this->connection)
                ->table("usraes.input_devo_fija_plano")
                ->select("plano")
                ->where("numero_reporte", $numReporte)
                // ->where("ticket", $ticket)
                ->where("departamento", $row->departamento)
                ->where("provincia", $row->provincia)
                ->where("distrito", $row->distrito)
                ->get();
            }
            $data->planos = $departamentos;
        }
        return $data;
    }

    public function getInputs()
    {
        $data = DB::connection($this->connection)->table("USRAES.INPUT_DEVO_FIJA")->get();
        return $data;
    }

    public function getReporteUsuariosAfectados($ticket)
    {
        return DB::connection($this->connection)->table("USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST a")
        ->selectRaw("rownum item,ticket,CODCLI CODIGO_CLIENTE,
        CASE
            WHEN LENGTH(NRO_DOC) <= 8 AND regexp_replace(NRO_DOC, '[0-9]*') IS NOT NULL THEN LPAD(NRO_DOC, 12, '0')
            WHEN LENGTH(NRO_DOC) < 8 THEN LPAD(NRO_DOC, 8, '0')
            WHEN 8 < LENGTH(NRO_DOC) AND LENGTH(NRO_DOC) < 11 THEN LPAD(NRO_DOC, 12, '0')
            ELSE NRO_DOC
            END AS NUMERO_DE_DOCUMENTO,
        NOMCLI NOMBRES_APELLIDOS,FAMILIA SERVICIO_ANALIZADO,NUMERO SERVICIO,DPTO,MONTO_PRINCIPAL")
        ->where("ticket", $ticket)
        ->whereNotNull("MONTO_PRINCIPAL")
        ->whereIn("ESTADO_CONTRATO", ["A", "G"])
        //->whereRaw("TRIM(MONTO_PRINCIPAL) != ''")
        /*->where(function($query) {
            $query->whereRaw("(CASE FUENTE
            WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
            WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
            ELSE 0 END
            ) = 1
            AND to_number(a.CICFAC_DEVOL) not in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)");
        })*/
        ->get();
    }

    public function getReportePostpago($ticket, $fuente, int $compensacionId)
    {
        return DB::connection($this->connection)->table("USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST a")
        ->selectRaw("TICKET,
        NUMERO MSISDN,
        NUMERO MSISDN_DEVOLVER,
        CR_NETO CARGO_LINEA,
        round(CR_NETO * 1.18, 2) CARGO_LINEA_IGV,
        MONTO_PRINCIPAL MTO_DEV,
        ROUND(MONTO_PRINCIPAL * 1.18, 2) MTO_DEV_IGV,
        CASE {$compensacionId}
        WHEN 1 THEN
        ROUND(CASE
            WHEN MINUTOS_AFECTACION >= 1440 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 4
            WHEN MINUTOS_AFECTACION >= 700 AND MINUTOS_AFECTACION < 1440 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 3.5
            WHEN MINUTOS_AFECTACION >= 300 AND MINUTOS_AFECTACION < 700 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 3
            WHEN MINUTOS_AFECTACION >= 60 AND MINUTOS_AFECTACION < 300 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 2.5
            ELSE 0
        END, 2) ELSE NULL END AS COMPENSACION,
        CASE {$compensacionId}
        WHEN 1 THEN
        ROUND((CASE
            WHEN MINUTOS_AFECTACION >= 1440 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 4
            WHEN MINUTOS_AFECTACION >= 700 AND MINUTOS_AFECTACION < 1440 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 3.5
            WHEN MINUTOS_AFECTACION >= 300 AND MINUTOS_AFECTACION < 700 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 3
            WHEN MINUTOS_AFECTACION >= 60 AND MINUTOS_AFECTACION < 300 THEN (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES) * 2.5
            ELSE 0
        END) / (ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES), 1)
        ELSE NULL END AS FACTOR_MULTIPLICATIVO,
        INTERES,
        TASA,
        ROUND(ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES, 2) MTO_TOTAL_DEV_IGV,
        CUSTCODE,
        CASE FUENTE WHEN 'SGA' THEN CODCLI ELSE CUSTOMER_ID END CUSTOMER_ID,
        IDINTPROD_DEVOL IDINSTPROD,
        CO_ID CO_ID_DEVOLVER,
        CICFAC_DEVOL CICLOFACTURACION,
        FUENTE,
        to_char(CASE FUENTE WHEN 'SGA' THEN FCHINI_INST ELSE FECHAALTA END, 'YYYY-MM-DD') FECHA_ALTA,
        to_char(CASE FUENTE WHEN 'SGA' THEN FCHINI_INST ELSE FECHAALTA END, 'YYYY-MM-DD') FECHA_ACTIVACION,
        'Dev. por interrupcion del ' || to_char(FEC_INI_INCIDENCIA, 'DD/MM/YYYY') || '. Tasa aplicada  0.01%' GLOSARIO")
        ->where("ticket", $ticket)
        ->where("fuente", $fuente)
        ->whereNotNull("MONTO_PRINCIPAL")
        ->whereIn("ESTADO_CONTRATO", ["A", "G"])
        ->where("COMENTARIOS", 'like', '%POSTPAGO%')
        //->where("MONTO_PRINCIPAL", '!=', '')
        /*->where(function($query) {
            $query->whereRaw("(CASE FUENTE
            WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
            WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
            ELSE 0 END
            ) = 1
            AND to_number(a.CICFAC_DEVOL) not in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)");
        })*/
        ->get();
    }

    public function getFuentesReportePostpago($ticket)
    {
        return DB::connection($this->connection)->table("USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST")
        ->select("fuente")
        ->where("ticket", $ticket)
        ->whereNotNull("MONTO_PRINCIPAL")
        ->where("ESTADO_CONTRATO", ["A", "G"])
        ->where("COMENTARIOS", 'like', '%POSTPAGO%')
        /*->where(function($query) {
            $query->whereRaw("(CASE FUENTE
            WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
            WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
            ELSE 0 END
            ) = 1
            AND to_number(CICFAC_DEVOL) not in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)");
        })*/
        ->groupBy("fuente")
        ->get();
    }

    public function getReportePrepago($ticket)
    {
        return DB::connection($this->connection)->table("USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST a")
        ->selectRaw("NUMERO,
        MSISDN_DEVOLVER MSISDN_DEVOL,
        ROUND(ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES, 2)*100 CENTIMOS,
        'Dev. por interrupcion del '||to_char(fec_ini_incidencia, 'dd/mm/yyyy')||'. Tasa aplicada  0.01%' GLOSA")
        ->where("ticket", $ticket)
        ->whereIn("ESTADO_CONTRATO", ["A", "G"])
        ->where("COMENTARIOS", 'like', '%PREPAGO%')
        ->get();
    }

    public function createInput(
        string $numReporte,
        string $username,
        string $servicioAfectado,
        DateTime $fechaIni,
        DateTime $fechaFin,
        int $mesesInteres,
        string $departamento,
        string $provincia,
        string $distrito,
        array $planos
    ) {
        DB::table("USRAES.INPUT_DEVO_FIJA")
        ->insert([
            "numero_reporte" => $numReporte,
            "username" => $username,
            "departamento" => $departamento,
            "provincia" => $provincia,
            "distrito" => $distrito,
            "servicio_afectado" => $servicioAfectado,
            "fecha_ini" => $fechaIni,
            "fecha_fin" => $fechaFin,
            "meses" => $mesesInteres,
        ]);
        
        foreach ($planos as $plano) {
            DB::table("USRAES.INPUT_DEVO_FIJA_PLANO")
            ->insert([
                "numero_reporte" => $numReporte,
                "departamento" => $departamento,
                "provincia" => $provincia,
                "distrito" => $distrito,
                // "ticket" => $ticket,
                "plano" => $plano,
            ]);
        }
    }

    public function createPlanoInput(
        string $numReporte,
        string $departamento,
        string $provincia,
        string $distrito,
        array $planos
    ) {
        foreach($planos as $plano){
            DB::table("USRAES.INPUT_DEVO_FIJA_PLANO")
            ->insert([
                "numero_reporte" => $numReporte,
                "departamento" => $departamento,
                "provincia" => $provincia,
                "distrito" => $distrito,
                // "ticket" => $ticket,
                "plano" => $plano,
            ]);
        }
    }

    public function createServicioAfectadoInput(
        string $numReporte,
        string $username,
        int $servicioAfectadoId,
        DateTime $fechaIni,
        DateTime $fechaFin,
        int $mesesInteres,
        int $compensacionId
    ) {
        DB::table("USRAES.INPUT_DEVO_FIJA")
        ->insert([
            "numero_reporte" => $numReporte,
            "username" => $username,
            // "departamento" => $departamento,
            // "provincia" => $provincia,
            // "distrito" => $distrito,
            "servicio_afectado_id" => $servicioAfectadoId,
            "fecha_ini" => $fechaIni,
            "fecha_fin" => $fechaFin,
            "meses" => $mesesInteres,
            "compensacion_id" => $compensacionId,
        ]);
    }

    public function updateTicketServicioInputByNumReporte(string $numReporte, int $servicioAfectadoId, ?string $ticket)
    {
        DB::table("USRAES.INPUT_DEVO_FIJA")
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["ticket" => $ticket]);
        
        // DB::table("USRAES.INPUT_DEVO_FIJA_PLANO")
        // ->where("numero_reporte", $numReporte)
        // ->where("servicio_afectado_id", $servicioAfectadoId)
        // ->update(["ticket" => $ticket]);
    }

    public function deleteServicioInput(string $numReporte, int $servicioAfectadoId)
    {
        DB::table("USRAES.INPUT_DEVO_FIJA")
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->delete();

        // DB::table("USRAES.INPUT_DEVO_FIJA_PLANO")
        // ->where("numero_reporte", $numReporte)
        // ->where("servicio_afectado_id", $servicioAfectadoId)
        // ->delete();
    }

    public function deletePlanoInput(string $numReporte)
    {
        DB::table("USRAES.INPUT_DEVO_FIJA_PLANO")
        ->where("numero_reporte", $numReporte)
        ->delete();
    }

    public function updateReporte($ticket, $departamento, $data)
    {
        DB::statement("DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
            V_DEPARTAMENTO VARCHAR2(100):= :p_departamento;
        BEGIN
            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST SET
            mto_dev_facturacion = NULL,
            mto_dif_facturacion = NULL,
            factura_aplicada = NULL,
            fecha_devolucion = NULL,
            fecha_registro_devolucion = NULL,
            observacion = NULL,
            fecha_baja = NULL
            WHERE TICKET= V_TICKET AND DPTO = V_DEPARTAMENTO;
            COMMIT;

            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL SET
            ACREDITADOS = 0,
            NO_ACREDITADOS = 0
            WHERE TICKET= V_TICKET AND DEPARTAMENTO = V_DEPARTAMENTO;
        END;", ["p_ticket" => $ticket, "p_departamento" => $departamento]);

        foreach($data as $row){
            DB::table("USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST")
            ->where("ticket", $ticket)
            ->where("numero", $row["msisdn"])
            ->where("fuente", $row["fuente"])
            ->update([
                "mto_dev_facturacion" => $row["mto_dev_facturacion"],
                "mto_dif_facturacion" => $row["mto_dif_facturacion"],
                "factura_aplicada" => $row["factura_aplicada"],
                "fecha_devolucion" => $row["fecha_devolucion"],
                "fecha_registro_devolucion" => $row["fecha_registro_devolucion"],
                "observacion" => $row["observacion"],
                "fecha_baja" => $row["fecha_baja"],
            ]);
        }

        DB::statement("DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
            V_DEPARTAMENTO VARCHAR2(100):= :p_departamento;
        BEGIN
            MERGE INTO USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL A
            USING (
                SELECT
                TICKET,FAMILIA,
                SUM(CASE WHEN FACTURA_APLICADA IS NOT NULL AND FECHA_DEVOLUCION IS NOT NULL THEN 1 ELSE 0 END) ACREDITADOS
                FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET= V_TICKET and DPTO = V_DEPARTAMENTO
                AND MONTO_PRINCIPAL is not null
                AND ESTADO_CONTRATO = 'A'
                AND (CASE FUENTE
                    WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
                    WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
                    ELSE 0 END
                ) = 1
                GROUP BY TICKET,FAMILIA
            ) B
            ON (A.TICKET = B.TICKET AND A.SERVICIO_AFECTADO = B.FAMILIA)
            WHEN MATCHED THEN UPDATE SET
            A.ACREDITADOS = B.ACREDITADOS
            WHERE TICKET= V_TICKET and DEPARTAMENTO = V_DEPARTAMENTO;
            COMMIT;

            UPDATE USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL SET
            NO_ACREDITADOS = ABONADOS_AFECTADOS - ACREDITADOS
            WHERE TICKET= V_TICKET AND DEPARTAMENTO = V_DEPARTAMENTO;
            COMMIT;
        END;", ["p_ticket" => $ticket, "p_departamento" => $departamento]);
    }

    private function exec_sql(array $queries)
    {
        foreach($queries as $row){
            $query = strlen($row['sql']) > 4000 ? substr($row['sql'], 0, 4000) : $row['sql'];
            $params = null;
            if(array_key_exists('params', $row)){
                $params = json_encode($row['params']);
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
            DB::table('prg_seguimiento_dblog')
            ->insert([
                'username' => $this->userIdentifier,
                'modulo' => 'extraccion-fija',
                'process_id' => getmygid(),
                'query' => $query,
                'params' => $params,
            ]);
        }
    }
}
