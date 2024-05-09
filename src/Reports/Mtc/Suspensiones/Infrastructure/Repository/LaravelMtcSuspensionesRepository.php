<?php

namespace AMovil\Reports\Mtc\Suspensiones\Infrastructure\Repository;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\Mtc\Suspensiones\Domain\MtcSuspensionesRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class LaravelMtcSuspensionesRepository implements MtcSuspensionesRepository
{
    private $authService;
    private $userIdentifier;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->userIdentifier = $authService->getUserIdentifier();
    }

    public function getReporte(DateTime $periodo, array $suspensiones)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        //$dt = new DateTime();
        //$dt->modify('-2 days');
        //$str_periodo = $dt->format('Ym');

        $str_periodo = $periodo->format('Ym');

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_PLANTILLA_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_PLANTILLA_{$this->userIdentifier}
            (
            ASNV_IDENTIFICADORGRUPOMTC VARCHAR2(20),
            ASNN_IDENTIFICADOR VARCHAR2(30), 
            ASNC_TIPO_REPORTE VARCHAR2(20),
            ASND_FECHA_REPORTE  VARCHAR2(20),
            ASNC_TIPO_TELEFONICO  VARCHAR2(10),
            ASNV_NUMERO_LINEA VARCHAR2(20),
            ASNC_TIPO_SUSPENCION  VARCHAR2(20),
            ASNN_NUM_DIAS_SUSPENDE  VARCHAR2(20),
            ASNV_MENSAJE_MTC  VARCHAR2(200),
            ASNC_ESTADO VARCHAR2(10) DEFAULT 'N',
            ASND_FECHA_INI_SUSPENSION DATE,
            ASND_FECHA_FIN_SUSPENSION DATE,
            ASNC_FLAG_REACTIVADO  NUMBER,
            ASND_FECHA_REACTIVACION DATE,
            ASNC_MOTIVO_NOSUSPENSION  VARCHAR2(10) DEFAULT '01',
            ASND_FECHA_BAJA DATE,
            ASNC_TIPO_TITULAR VARCHAR2(10),
            ASNC_TIPO_CONTRATO VARCHAR2(10), 
            ASNC_TIPO_DOCTITULAR  VARCHAR2(10),
            ASNV_NUM_DOCTITULAR VARCHAR2(20),
            ASNV_NOMBRETITULAR  VARCHAR2(50),
            ASND_FEC_INICONTRATO  DATE,
            ASND_FEC_FINCONTRATO  DATE,
            ASNV_DIRECCION  VARCHAR2(200),
            ASNV_UBIGEO VARCHAR2(20)
            )"
        ];

        $this->exec_sql($array_sql);

        $suspensiones_to_insert = [];
        foreach($suspensiones as $row){
            $suspensiones_to_insert[] = [
                'ASNV_IDENTIFICADORGRUPOMTC' => $row[0],
                'ASNN_IDENTIFICADOR' => $row[1],
                'ASNC_TIPO_TELEFONICO' => $row[2],
                'ASNV_NUMERO_LINEA' => $row[3]
            ];
        }
        DB::table("USRAES.TMP_PLANTILLA_{$this->userIdentifier}")->insert($suspensiones_to_insert);

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_LINEAS_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.T_LINEAS_{$this->userIdentifier} AS SELECT ASNV_NUMERO_LINEA as LINEAS FROM USRAES.TMP_PLANTILLA_{$this->userIdentifier}"
        ];
        /*$array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_LLAM_MAL_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.T_LLAM_MAL_{$this->userIdentifier} AS
            SELECT B.MSISDN,B.MES,DECODE(B.IDPLATAFORMA,1,'PREPAGO',2,'POSTPAGO') MODALIDAD,
            CASE WHEN B.IDPLATAFORMA ='2' AND B.IDESTADO='1' THEN 'ACTIVO'
                 WHEN B.IDPLATAFORMA ='2' AND B.IDESTADO='2' THEN 'DESACTIVO'
                 WHEN B.IDPLATAFORMA ='2' AND B.IDESTADO='3' THEN 'SUSPENDIDO'
                 WHEN B.IDPLATAFORMA ='2' AND B.IDESTADO='4' THEN 'ON HOLD'
                 WHEN B.IDPLATAFORMA ='1' AND B.IDESTADO='1' THEN 'PREACTIVO'
                 WHEN B.IDPLATAFORMA ='1' AND B.IDESTADO='2' THEN 'ACTIVO'
                 WHEN B.IDPLATAFORMA ='1' AND B.IDESTADO='3' THEN 'PERIODO DE GRACIA'
                 WHEN B.IDPLATAFORMA ='1' AND B.IDESTADO='4' THEN 'DESACTIVO' END ULTIMO_ESTADO_REGISTRADO,
            B.TIPO_DOCUMENTO,B.NRO_DOCUMENTO, B.NOMBRES||' '||B.APELLIDOS NOMBRES
            FROM DM.F_M_ABONADOS B WHERE B.MSISDN IN (SELECT '51'||to_char(to_number(LINEAS)) FROM USRAES.T_LINEAS_{$this->userIdentifier}) AND B.MES ='{$str_periodo}'"
        ];*/
        
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier}(
            MSISDN VARCHAR2(20),
            MES VARCHAR2(6),
            MODALIDAD VARCHAR2(8),
            ULTIMO_ESTADO_REGISTRADO VARCHAR2(17),
            TIPO_DOCUMENTO VARCHAR2(30),
            NRO_DOCUMENTO VARCHAR2(30),
            NOMBRES VARCHAR2(201),
            CCNAME VARCHAR2(400),
            OPERADORA CHAR(5),
            DEPARTAMENTO VARCHAR2(40),
            PROVINCIA VARCHAR2(70),
            DISTRITO VARCHAR2(40),
            FECHA_DATA DATE
            )"
        ];
        /*$array_sql[] = [
            'sql' => "DECLARE
                CURSOR VA IS SELECT NRO_DOCUMENTO FROM USRAES.T_LLAM_MAL_{$this->userIdentifier} WHERE MODALIDAD='PREPAGO';
                DNI VARCHAR2(20);
            BEGIN
                EXECUTE IMMEDIATE 'TRUNCATE TABLE USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier}';
                OPEN VA;
                LOOP
                    FETCH VA INTO DNI;
                    EXIT WHEN VA%NOTFOUND;
                    INSERT INTO USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier}
                SELECT C.*, R.CCNAME,'CLARO' OPERADORA,R.CCCITY DEPARTAMENTO,R.CCSTREET PROVINCIA,R.CCADDR3 DISTRITO,
                R.CCENTDATE FECHA_DATA FROM USRAES.T_LLAM_MAL_{$this->userIdentifier} C LEFT JOIN
                (SELECT * FROM DMRED.CCONTACT_ALL R WHERE R.CSCOMPREGNO=DNI AND R.CCBILL='X' AND R.CUSTOMER_ID=
                (SELECT MAX (E.CUSTOMER_ID) FROM DMRED.CCONTACT_ALL E
                WHERE E.CSCOMPREGNO=DNI AND E.CCBILL='X')) R
                ON C.NRO_DOCUMENTO=R.CSCOMPREGNO  WHERE C.NRO_DOCUMENTO=DNI;
                COMMIT;
                END LOOP;
                CLOSE VA;
            END;"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                INSERT INTO USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier}
                select A.*,C.CCNAME , 'CLARO' OPERADORA, C.CCCITY DEPARTAMENTO ,C.CCSTREET PROVINCIA,C.CCADDR3 DISTRITO,C.CCENTDATE FECHA_DATA 
                FROM USRAES.T_LLAM_MAL_{$this->userIdentifier} A,DM.F_M_ABONADOS B,DMRED.CCONTACT_ALL C  
                WHERE A.MODALIDAD='POSTPAGO' AND A.MSISDN=B.MSISDN AND B.ID_CLIENTE=C.CUSTOMER_ID AND C.CCBILL='X' AND B.MES = '{$str_periodo}';

                COMMIT;
            END;"
        ];*/

        $array_sql[] = [
            'sql' => "BEGIN
                INSERT INTO USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier}(
                    MSISDN, MES, MODALIDAD, ULTIMO_ESTADO_REGISTRADO, TIPO_DOCUMENTO, NRO_DOCUMENTO, NOMBRES, CCNAME, OPERADORA, DEPARTAMENTO,
                    PROVINCIA, DISTRITO, FECHA_DATA
                )
                select  /*+ PARALLEL(4)*/ MSISDN,MES,MODALIDAD,ULTIMO_ESTADO_REGISTRADO,
                TIPO_DOCUMENTO,NRO_DOCUMENTO,NOMBRES,CCNAME,OPERADORA,DEPARTAMENTO,PROVINCIA,
                DISTRITO,FECHA_DATA from (
                    select  /*+ PARALLEL(4)*/ customer_account_sc ,SUBSCRIPTION_ACCESS_NUMBER MSISDN,
                    PERIOD MES,AGREEMENT_MODE MODALIDAD,agreement_status ULTIMO_ESTADO_REGISTRADO,
                    TRUNC(AGREEMENT_START_DATE, 'DD') FECHA_DATA,TO_CHAR(AGREEMENT_END_DATE,'yyyy-mm-dd') FECHA_BAJA,ID_CARD_TYPE_VALUE TIPO_DOCUMENTO,
                    ID_CARD_VALUE NRO_DOCUMENTO,CUSTOMER_FULL_NAME NOMBRES,CUSTOMER_FULL_NAME CCNAME,'CLARO' OPERADORA,
                    CASE WHEN CUSTOMER_ACCOUNT_BILLING_DEPARTMENT IS NULL THEN INSTALLATION_DEPARTMENT ELSE CUSTOMER_ACCOUNT_BILLING_DEPARTMENT END DEPARTAMENTO,
                    CASE WHEN CUSTOMER_ACCOUNT_BILLING_PROVINCE IS NULL THEN INSTALLATION_PROVINCE ELSE CUSTOMER_ACCOUNT_BILLING_PROVINCE END PROVINCIA,
                    CASE WHEN CUSTOMER_ACCOUNT_BILLING_DISTRICT IS NULL THEN INSTALLATION_DISTRICT ELSE CUSTOMER_ACCOUNT_BILLING_DISTRICT END DISTRITO,
                    row_number() over(PARTITION by SUBSCRIPTION_ACCESS_NUMBER order by AGREEMENT_START_DATE desc) flag
                    from DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$str_periodo})
                    WHERE SUBSCRIPTION_ACCESS_NUMBER IN (SELECT '51'||to_char(to_number(LINEAS)) FROM USRAES.T_LINEAS_{$this->userIdentifier}) 
                ) where flag=1;
                COMMIT;
            END;"
        ];

        // PASO 3
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.REPORTE_POST_MTC_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.REPORTE_POST_MTC_{$this->userIdentifier} AS 
            SELECT DISTINCT
                 SUBSTR(MSISDN, 3, 10) AS MSISDN,
                  HI.CUSTOMER_FULL_NAME AS NOMBRES,
                  TIPO_DOCUMENTO,
                  NRO_DOCUMENTO,
                  trunc(sysdate) AS INICIO_SUSPENSION,
                  trunc(sysdate + 30) TERMINO_SUSPENSION,
                  trunc(sysdate + 31) FECHA_REACTIVACION,
                  OPERADORA,
                   nvl(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO)) distrito,
                   NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA)) provincia,
                   NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO)) departamento,
                  MODALIDAD,
                  (CASE
                    WHEN SUBSTR(MSISDN, 3, 10) LIKE '9%' THEN
                     'MOVIL'
                    ELSE
                     'FIJO'
                  END) AS TIPO_SERVICIO,
                  'S' estado,
                  0 estado_Reactivacion,
                  '  ' motivo_no_suspension,
                   (case  when   extract(year from hi.subscription_end_date) <=  extract(year from sysdate) then  trunc(hi.subscription_end_date)
                      else  null end ) fecha_baja_linea,
                  'R' tipo_titular,
                  'O' TIP_CONTRATO_LINEA,
                  ( CASE
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) = 'DNI' THEN
                     'D'
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) = 'RUC' THEN
                     'R'
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) IN ('CARNET EXTRANJERÍA','CE','C.E','CPP') THEN
                     'C'
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) = 'PASAPORTE' THEN
                     'P'
                  END ) AS TIPO_DOC,
                  TRUNC(HI.SUBSCRIPTION_START_DATE) FECHA_INICIO_CONTRATO,
                   TRUNC(hi.subscription_end_date) FECHA_FIN_CONTRATO,
                  ( CASE WHEN  (NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO)) || '/' || NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA))  || '/' ||
                   NVL(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO)))  = '//' THEN NULL 
                   else NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO)) || '/' || NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA))  || '/' ||
                   NVL(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO))END)  AS DIRECCION_CLIENT ,
                (SELECT distinct RE.ID_UBIGEO   FROM DWA.AYF_RTM_UBIGEO_INEI_REGUL RE WHERE RE.DSC_DISTRITO = NVL(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO))
                   AND RE.DSC_PROVINCIA = NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA)) AND RE.DSC_DEPARTAMENTO = NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO))) CODIGO_UBIGEO,
                   hI.subscription_status  estado_subs ,
                   hI.AGREEMENT_REASON_STATUS_DESC
             FROM USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier} dir, dwa.DW_M_SUBSCRIPTION_HIST  PARTITION (P_{$str_periodo}) HI -- EL PARTITION IGUAL TIENE QUE TOMAR EL MES Y AÑO DE LA FECHA -2 DIAS
            where modalidad = 'POSTPAGO' and hi.PERIOD = dir.mes   AND DIR.NRO_DOCUMENTO = HI.ID_CARD_VALUE
            AND SUBSTR(hi.subscription_access_number,3,10) = SUBSTR(dir.msisdn,3,10)
              AND DIR.MES ='{$str_periodo}' -- DE LA FECHA DE EJECUCION SE RESTA 2 DIAS Y SE SACA EL AÑO-MES ESO SE INGRESA EN EL CAMPO DIR.MES
              AND hi.AGREEMENT_MODE = 'POSTPAGO'"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.REPORTE_PRE_MTC_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.REPORTE_PRE_MTC_{$this->userIdentifier} AS
            SELECT DISTINCT
                  SUBSTR(MSISDN, 3, 10) AS MSISDN,
                  HI.CUSTOMER_FULL_NAME AS NOMBRES,
                  TIPO_DOCUMENTO,
                  NRO_DOCUMENTO,
                  trunc(sysdate) AS INICIO_SUSPENSION,
                  trunc(sysdate + 30) TERMINO_SUSPENSION,
                  trunc(sysdate + 31) FECHA_REACTIVACION,
                  OPERADORA,
                   nvl(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO)) distrito,
                   NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA)) provincia,
                   NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO)) departamento,
                  MODALIDAD,
                  (CASE
                    WHEN SUBSTR(MSISDN, 3, 10) LIKE '9%' THEN
                     'MOVIL'
                    ELSE
                     'FIJO'
                  END) AS TIPO_SERVICIO,
                    'S' estado,
                  0 estado_Reactivacion,
                  '  ' motivo_no_suspension,
                (case  when   extract(year from hi.subscription_end_date) <=  extract(year from sysdate) then  trunc(hi.subscription_end_date)
                      else  null end ) fecha_baja_linea,-- fecha linea
                  'R' tipo_titular,
                  'P' TIP_CONTRATO_LINEA,
                ( CASE
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) = 'DNI' THEN
                     'D'
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) = 'RUC' THEN
                     'R'
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) IN ('CARNET EXTRANJERÍA','CE','C.E','CPP') THEN
                     'C'
                    WHEN UPPER(DIR.TIPO_DOCUMENTO) = 'PASAPORTE' THEN
                     'P'
                  END ) AS TIPO_DOC,
                TRUNC(HI.SUBSCRIPTION_START_DATE) FECHA_INICIO_CONTRATO,
                   TRUNC(hi.subscription_end_date) FECHA_FIN_CONTRATO,
                 ( CASE WHEN  (NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO)) || '/' || NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA))  || '/' ||
                   NVL(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO)))  = '//' THEN NULL 
                   else NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO)) || '/' || NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA))  || '/' ||
                   NVL(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO))END)  AS DIRECCION_CLIENT ,
                (SELECT distinct RE.ID_UBIGEO   
                FROM DWA.AYF_RTM_UBIGEO_INEI_REGUL RE 
                WHERE RE.DSC_DISTRITO = NVL(trim(hi.CUSTOMER_ACCOUNT_BILLING_DISTRICT),trim(DIR.DISTRITO))
                   AND RE.DSC_PROVINCIA = NVL(trim(hi.Customer_Account_Billing_Province),trim(DIR.PROVINCIA)) AND 
                   RE.DSC_DEPARTAMENTO = NVL(trim(hi.customer_account_billing_department),trim(DIR.DEPARTAMENTO))) CODIGO_UBIGEO,
                   hI.subscription_status  estado_subs ,
                   hI.AGREEMENT_REASON_STATUS_DESC
             FROM USRAES.T_LLAM_MAL_DIR_{$this->userIdentifier} dir, dwa.DW_M_SUBSCRIPTION_HIST  PARTITION (P_{$str_periodo}) HI
             where modalidad = 'PREPAGO' and hi.PERIOD = dir.mes   AND DIR.NRO_DOCUMENTO = HI.ID_CARD_VALUE
            AND SUBSTR(hi.subscription_access_number,3,10) = SUBSTR(dir.msisdn,3,10)
              AND DIR.MES ='{$str_periodo}'
              AND hi.AGREEMENT_MODE = 'PREPAGO'"
        ];

        // ##QUERY PARA SUSPENCIONES:
        //$this->generateReporteSuspensiones();

        $this->exec_sql($array_sql);
    }

    public function generateReporteSuspensiones(DateTime $periodo, array $suspensiones)
    {
        $this->getReporte($periodo, $suspensiones);

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_SUSPENSIONES_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_SUSPENSIONES_{$this->userIdentifier} AS
        SELECT 
               MSISDN, -- CAMPO COMODIN PARA IDENTIFICAR LINEA (quitarlo del resultado final en CSV)
               ESTADO,
               INICIO_SUSPENSION,
               TERMINO_SUSPENSION,
               ESTADO_REACTIVACION,    
               FECHA_REACTIVACION,    
               MOTIVO_NO_SUSPENSION, 
               FECHA_BAJA_LINEA,      
               TIPO_TITULAR,          
               TIP_CONTRATO_LINEA,    
               TIPO_DOC,              
               NRO_DOCUMENTO,         
               NOMBRES,                
               FECHA_INICIO_CONTRATO, 
               FECHA_FIN_CONTRATO,    
               DIRECCION_CLIENT,      
               CODIGO_UBIGEO ,        
               estado_subs,           
               AGREEMENT_REASON_STATUS_DESC, -- CAMPO COMODIN (quitarlo del resultado final en CSV)
               'POSTPAGO' MODALIDAD
          FROM USRAES.REPORTE_POST_MTC_{$this->userIdentifier}             -- CAMPO COMODIN (quitarlo del resultado final en CSV)
        UNION ALL
        SELECT 
               MSISDN, -- CAMPO COMODIN PARA IDENTIFICAR LINEA (quitarlo del resultado final en CSV)
               ESTADO,
               INICIO_SUSPENSION,
               TERMINO_SUSPENSION,
               ESTADO_REACTIVACION,
               FECHA_REACTIVACION, 
               MOTIVO_NO_SUSPENSION,         
               FECHA_BAJA_LINEA,        
               TIPO_TITULAR,            
               TIP_CONTRATO_LINEA,      
               TIPO_DOC,                
               NRO_DOCUMENTO,           
               NOMBRES,                 
               FECHA_INICIO_CONTRATO,   
               FECHA_FIN_CONTRATO,      
               DIRECCION_CLIENT,        
               CODIGO_UBIGEO ,          
               estado_subs,             -- CAMPO COMODIN (quitarlo del resultado final en CSV)
               AGREEMENT_REASON_STATUS_DESC,      -- CAMPO COMODIN (quitarlo del resultado final en CSV)
               'PREPAGO' MODALIDAD
          FROM USRAES.REPORTE_PRE_MTC_{$this->userIdentifier}"
        ];

        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier} AS
            SELECT * FROM(
            SELECT C.*,ROW_NUMBER() OVER(PARTITION BY C.MSISDN ORDER BY C.FECHA_BAJA_LINEA DESC) RA FROM(
            SELECT * FROM USRAES.TMP_SUSPENSIONES_{$this->userIdentifier}
            )C
            )WHERE RA=1"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                UPDATE USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier}
                SET ESTADO='N' , MOTIVO_NO_SUSPENSION='02' ,INICIO_SUSPENSION =NULL,TERMINO_SUSPENSION=NULL
                WHERE AGREEMENT_REASON_STATUS_DESC LIKE '%PORT OUT%';
        
                UPDATE USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier}
                SET ESTADO='N' , MOTIVO_NO_SUSPENSION='03' ,INICIO_SUSPENSION =NULL,TERMINO_SUSPENSION=NULL
                WHERE ESTADO_SUBS='D' AND AGREEMENT_REASON_STATUS_DESC NOT LIKE '%PORT OUT%';
        
                COMMIT;
            END;"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                MERGE INTO USRAES.TMP_PLANTILLA_{$this->userIdentifier} TF USING USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier} TMP
                ON (to_char(to_number(TF.ASNV_NUMERO_LINEA))=TMP.MSISDN)
                WHEN MATCHED THEN UPDATE SET
        
                TF.ASNC_ESTADO=TMP.ESTADO,
                TF.ASND_FECHA_INI_SUSPENSION=TMP.INICIO_SUSPENSION,
                TF.ASND_FECHA_FIN_SUSPENSION=TMP.TERMINO_SUSPENSION,
                TF.ASNC_FLAG_REACTIVADO=TMP.ESTADO_REACTIVACION,
                TF.ASND_FECHA_REACTIVACION=TMP.FECHA_REACTIVACION,
                TF.ASNC_MOTIVO_NOSUSPENSION=TMP.MOTIVO_NO_SUSPENSION,
                TF.ASND_FECHA_BAJA=TMP.FECHA_BAJA_LINEA,
                TF.ASNC_TIPO_TITULAR=TMP.TIPO_TITULAR,
                TF.ASNC_TIPO_CONTRATO=TMP.TIP_CONTRATO_LINEA,
                TF.ASNC_TIPO_DOCTITULAR=TMP.TIPO_DOC,
                TF.ASNV_NUM_DOCTITULAR=TMP.NRO_DOCUMENTO,
                TF.ASNV_NOMBRETITULAR=TMP.NOMBRES,
                TF.ASND_FEC_INICONTRATO=TMP.FECHA_INICIO_CONTRATO,
                TF.ASND_FEC_FINCONTRATO=TMP.FECHA_FIN_CONTRATO,
                TF.ASNV_DIRECCION=TMP.DIRECCION_CLIENT,
                TF.ASNV_UBIGEO=TMP.CODIGO_UBIGEO;

                COMMIT;
            END;"
        ];

        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_PLANTILLA_SUS_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];

        $array_sql[] = [
            "sql" => "CREATE TABLE USRAES.TMP_PLANTILLA_SUS_{$this->userIdentifier} AS SELECT * FROM USRAES.TMP_PLANTILLA_{$this->userIdentifier}"
        ];

        $this->exec_sql($array_sql);
    }

    public function generateReporteTitularidad(DateTime $periodo, $suspensiones)
    {
        $this->getReporte($periodo, $suspensiones);

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_TITULARES_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_TITULARES_{$this->userIdentifier} AS   
            select 
                   MSISDN, -- CAMPO COMODIN PARA IDENTIFICAR LINEA (quitarlo del resultado final en CSV)
                   TIPO_TITULAR ASNC_TIPO_TITULAR,           
                   TIP_CONTRATO_LINEA ASNC_TIPO_CONTRATO,
                   TIPO_DOC ASNC_TIPO_DOCTITULAR,
                   NRO_DOCUMENTO ASNV_NUM_DOCTITULAR,
                   NOMBRES               ASNV_NOMBRETITULAR,
                   FECHA_INICIO_CONTRATO ASND_FEC_INICONTRATO,
                   FECHA_FIN_CONTRATO    ASND_FEC_FINCONTRATO,
                   DIRECCION_CLIENT      ASNV_DIRECCION,
                   CODIGO_UBIGEO         ASNV_UBIGEO , 
                   estado_subs           ESTADO_SUBS ,       -- CAMPO COMODIN (quitarlo del resultado final en CSV)
                   AGREEMENT_REASON_STATUS_DESC,             -- CAMPO COMODIN (quitarlo del resultado final en CSV)
                   'POSTPAGO' MODALIDAD
              from USRAES.REPORTE_POST_MTC_{$this->userIdentifier}
            UNION ALL
            select 
                   MSISDN, -- CAMPO COMODIN PARA IDENTIFICAR LINEA (quitarlo del resultado final en CSV)
                   TIPO_TITULAR ASNC_TIPO_TITULAR,
                   TIP_CONTRATO_LINEA ASNC_TIPO_CONTRATO,
                   TIPO_DOC ASNC_TIPO_DOCTITULAR,
                   NRO_DOCUMENTO ASNV_NUM_DOCTITULAR,
                   NOMBRES               ASNV_NOMBRETITULAR,
                   FECHA_INICIO_CONTRATO ASND_FEC_INICONTRATO,
                   FECHA_FIN_CONTRATO    ASND_FEC_FINCONTRATO,
                   DIRECCION_CLIENT ASNV_DIRECCION,
                   CODIGO_UBIGEO         ASNV_UBIGEO  ,
                   estado_subs,                              -- CAMPO COMODIN (quitarlo del resultado final en CSV)
                   AGREEMENT_REASON_STATUS_DESC,              -- CAMPO COMODIN (quitarlo del resultado final en CSV) 
                   'PREPAGO' MODALIDAD  
              from USRAES.REPORTE_PRE_MTC_{$this->userIdentifier}"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_TITULARES_1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_TITULARES_1_{$this->userIdentifier} AS
            SELECT * FROM(
            SELECT C.*,ROW_NUMBER() OVER(PARTITION BY C.MSISDN ORDER BY C.ASND_FEC_FINCONTRATO DESC) RA FROM(
            SELECT * FROM USRAES.TMP_TITULARES_{$this->userIdentifier}
            )C
            )WHERE RA=1"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                UPDATE USRAES.TMP_TITULARES_1_{$this->userIdentifier}
                SET ASNV_DIRECCION='SIN DIRECCIÓN POR SER PREPAGO'
                WHERE ASNC_TIPO_TITULAR ='R' AND ASNC_TIPO_CONTRATO='P' AND ASNV_DIRECCION IS NULL;
                
                COMMIT;
            END;"
        ];

        $array_sql[] = [
            'sql' => "DECLARE
                CURSOR CUR_UBIGEOS_NULL is
                SELECT
                MSISDN,
                ASNV_NUM_DOCTITULAR,
                substr(asnv_direccion_NOTNULL,INSTR(asnv_direccion_NOTNULL,'/')+1) AS asnv_direccion,
                null ubigeo
                FROM (
                select MSISDN,ASND_FEC_INICONTRATO,ASNV_NUM_DOCTITULAR,substr(asnv_direccion,INSTR(asnv_direccion,'/')+1) asnv_direccion_NOTNULL,
                asnv_direccion,
                asnv_ubigeo 
                from USRAES.TMP_TITULARES_1_{$this->userIdentifier}
                where asnv_ubigeo is null and asnv_direccion not like '%SIN DIRECCIÓN POR SER PREPAGO%');
            
                V_UBIGEO VARCHAR2(20);
            BEGIN
                FOR V_ROW IN CUR_UBIGEOS_NULL
                LOOP
                    BEGIN
                        select 
                        --d.depav_descripcion,
                        --p.provv_descripcion,
                        --t.distv_descripcion,
                        t.Ubigeo_Inei INTO V_UBIGEO
                        from
                        DWS.SA_SECT_DEPARTAMENTO d, DWS.SA_SECT_PROVINCIA p, DWS.SA_SECT_DISTRITO t
                        WHERE d.depac_codigo=p.depac_codigo
                        AND p.provc_codigo=t.provc_codigo
                        AND T.DISTV_DESCRIPCION LIKE '%'||V_ROW.asnv_direccion||'%'
                        FETCH FIRST 1 ROWS ONLY;
            
                        UPDATE USRAES.TMP_TITULARES_1_{$this->userIdentifier} set
                        ASNV_UBIGEO = V_UBIGEO
                        WHERE msisdn = V_ROW.MSISDN and ASNV_NUM_DOCTITULAR = v_ROW.ASNV_NUM_DOCTITULAR;
                        COMMIT;
                    EXCEPTION
                        WHEN OTHERS THEN
                            V_UBIGEO := NULL;
                    END;
                END LOOP;
            END;"
        ];


        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_PLANTILLA_TITU_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_PLANTILLA_TITU_{$this->userIdentifier}
            (
            ASNV_IDENTIFICADORGRUPOMTC  VARCHAR2(20),
            ASNN_IDENTIFICADOR  VARCHAR2(20),
            ASNC_TIPO_REPORTE   VARCHAR2(20),
            ASND_FECHA_REPORTE  VARCHAR2(20),
            ASNC_TIPO_TELEFONICO    VARCHAR2(10),
            ASNV_NUMERO_LINEA VARCHAR2(30)
            )"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                INSERT INTO USRAES.TMP_PLANTILLA_TITU_{$this->userIdentifier} SELECT ASNV_IDENTIFICADORGRUPOMTC,
                ASNN_IDENTIFICADOR,
                ASNC_TIPO_REPORTE,
                ASND_FECHA_REPORTE,
                ASNC_TIPO_TELEFONICO,
                ASNV_NUMERO_LINEA FROM USRAES.TMP_PLANTILLA_{$this->userIdentifier};

                COMMIT;
            END;"
        ];

        $this->exec_sql($array_sql);
    }

    public function generateReporteMensual(DateTime $periodo, $suspensiones)
    {
        $this->getReporte($periodo, $suspensiones);

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_REPOMENSUAL_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_REPOMENSUAL_{$this->userIdentifier} AS  
            select 
                   MSISDN, -- CAMPO COMODIN PARA IDENTIFICAR LINEA (quitarlo del resultado final en CSV)
                   TIPO_TITULAR ASNC_TIPO_TITULAR,
                   TIP_CONTRATO_LINEA ASNC_TIPO_CONTRATO,
                   TIPO_DOC ASNC_TIPO_DOCTITULAR,
                   NRO_DOCUMENTO ASNV_NUM_DOCTITULAR,
                   NOMBRES               ASNV_NOMBRETITULAR,
                   FECHA_INICIO_CONTRATO ASND_FEC_INICONTRATO,
                   FECHA_FIN_CONTRATO    ASND_FEC_FINCONTRATO,
                   DIRECCION_CLIENT      ASNV_DIRECCION,
                   CODIGO_UBIGEO         ASNV_UBIGEO , 
                   estado_subs           ESTADO_SUBS ,       -- CAMPO COMODIN (quitarlo del resultado final en CSV)
                   AGREEMENT_REASON_STATUS_DESC              -- CAMPO COMODIN (quitarlo del resultado final en CSV)
              from USRAES.REPORTE_POST_MTC_{$this->userIdentifier}
            UNION ALL
            select 
                   MSISDN, -- CAMPO COMODIN PARA IDENTIFICAR LINEA (quitarlo del resultado final en CSV)
                   TIPO_TITULAR ASNC_TIPO_TITULAR,
                   TIP_CONTRATO_LINEA ASNC_TIPO_CONTRATO,
                   TIPO_DOC ASNC_TIPO_DOCTITULAR,
                   NRO_DOCUMENTO ASNV_NUM_DOCTITULAR,
                   NOMBRES               ASNV_NOMBRETITULAR,
                   FECHA_INICIO_CONTRATO ASND_FEC_INICONTRATO,
                   FECHA_FIN_CONTRATO    ASND_FEC_FINCONTRATO,
                   DIRECCION_CLIENT      ASNV_DIRECCION,
                   CODIGO_UBIGEO         ASNV_UBIGEO  ,
                   estado_subs,                            -- CAMPO COMODIN (quitarlo del resultado final en CSV)
                   AGREEMENT_REASON_STATUS_DESC            -- CAMPO COMODIN (quitarlo del resultado final en CSV)
              from USRAES.REPORTE_PRE_MTC_{$this->userIdentifier}"
        ];

        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_REPOMENSUAL_1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_REPOMENSUAL_1_{$this->userIdentifier} AS
            SELECT * FROM(
            SELECT C.*,ROW_NUMBER() OVER(PARTITION BY C.MSISDN ORDER BY C.ASND_FEC_FINCONTRATO DESC) RA FROM(
            SELECT * FROM USRAES.TMP_REPOMENSUAL_{$this->userIdentifier}
            )C
            )WHERE RA=1"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                UPDATE USRAES.TMP_REPOMENSUAL_1_{$this->userIdentifier}
                SET ASNV_DIRECCION='SIN DIRECCIÓN POR SER PREPAGO'
                WHERE ASNC_TIPO_TITULAR ='R' AND ASNC_TIPO_CONTRATO='P' AND ASNV_DIRECCION IS NULL;
                
                COMMIT;
            END;"
        ];

        $array_sql[] = [
            'sql' => "DECLARE
                CURSOR CUR_UBIGEOS_NULL is
                SELECT
                MSISDN,
                ASNV_NUM_DOCTITULAR,
                substr(asnv_direccion_NOTNULL,INSTR(asnv_direccion_NOTNULL,'/')+1) AS asnv_direccion,
                null ubigeo
                FROM (
                select MSISDN,ASND_FEC_INICONTRATO,ASNV_NUM_DOCTITULAR,substr(asnv_direccion,INSTR(asnv_direccion,'/')+1) asnv_direccion_NOTNULL,
                asnv_direccion,
                asnv_ubigeo 
                from USRAES.TMP_REPOMENSUAL_1_{$this->userIdentifier}
                where asnv_ubigeo is null and asnv_direccion not like '%SIN DIRECCIÓN POR SER PREPAGO%');
            
                V_UBIGEO VARCHAR2(20);
            BEGIN
                FOR V_ROW IN CUR_UBIGEOS_NULL
                LOOP
                    BEGIN
                        select 
                        --d.depav_descripcion,
                        --p.provv_descripcion,
                        --t.distv_descripcion,
                        t.Ubigeo_Inei INTO V_UBIGEO
                        from
                        DWS.SA_SECT_DEPARTAMENTO d, DWS.SA_SECT_PROVINCIA p, DWS.SA_SECT_DISTRITO t
                        WHERE d.depac_codigo=p.depac_codigo
                        AND p.provc_codigo=t.provc_codigo
                        AND T.DISTV_DESCRIPCION LIKE '%'||V_ROW.asnv_direccion||'%'
                        FETCH FIRST 1 ROWS ONLY;
            
                        UPDATE USRAES.TMP_REPOMENSUAL_1_{$this->userIdentifier} set
                        ASNV_UBIGEO = V_UBIGEO
                        WHERE msisdn = V_ROW.MSISDN and ASNV_NUM_DOCTITULAR = v_ROW.ASNV_NUM_DOCTITULAR;
                        COMMIT;
                    EXCEPTION
                        WHEN OTHERS THEN
                            V_UBIGEO := NULL;
                    END;
                END LOOP;
            END;"
        ];

        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_PLANTILLA_REPOMENSUAL_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_PLANTILLA_REPOMENSUAL_{$this->userIdentifier}
            (
            ASNV_IDENTIFICADORGRUPOMTC  VARCHAR2(20),
            ASNN_IDENTIFICADOR  VARCHAR2(20),
            ASNC_TIPO_REPORTE VARCHAR2(20),
            ASND_FECHA_REPORTE  VARCHAR2(20),
            ASNC_TIPO_TELEFONICO  VARCHAR2(10),
            ASNV_NUMERO_LINEA VARCHAR2(30)
            )"
        ];

        $array_sql[] = [
            'sql' => "BEGIN
                INSERT INTO USRAES.TMP_PLANTILLA_REPOMENSUAL_{$this->userIdentifier}
                SELECT ASNV_IDENTIFICADORGRUPOMTC,
                ASNN_IDENTIFICADOR,
                ASNC_TIPO_REPORTE,
                ASND_FECHA_REPORTE,
                ASNC_TIPO_TELEFONICO,
                TO_CHAR(TO_NUMBER(ASNV_NUMERO_LINEA)) ASNV_NUMERO_LINEA
                FROM USRAES.TMP_PLANTILLA_{$this->userIdentifier};

                COMMIT;
            END;"
        ];
        
        $this->exec_sql($array_sql);
    }

    public function generateReporteNotificacion(DateTime $periodo, $suspensiones)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $str_periodo = $periodo->format('Ym');

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_TXT_NOTIFI_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_TXT_NOTIFI_{$this->userIdentifier} (
                ASNV_IDENTIFICADORGRUPOMTC varchar2(160),
                ASNN_IDENTIFICADOR varchar2(160),
                ASNC_TIPO_TELEFONICO varchar2(160),
                ASNV_NUMERO_LINEA varchar2(160),
                MENSAJE varchar2(160)
            )"
        ];
        $this->exec_sql($array_sql);
        
        $array_to_insert = [];
        foreach($suspensiones as $row){
            $array_to_insert[] = [
                'ASNV_IDENTIFICADORGRUPOMTC' => $row[0],
                'ASNN_IDENTIFICADOR' => $row[1],
                'ASNC_TIPO_TELEFONICO' => $row[2],
                'ASNV_NUMERO_LINEA' => $row[3],
                'MENSAJE' => $row[4]
            ];
        }
        DB::table("USRAES.TMP_TXT_NOTIFI_{$this->userIdentifier}")->insert($array_to_insert);

        $array_sql = [];
        $array_sql[] = [
            'sql' => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_NOTIFICACIONES_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
            END;"
        ];
        $array_sql[] = [
            'sql' => "CREATE TABLE USRAES.TMP_NOTIFICACIONES_{$this->userIdentifier} (
                ASNV_IDENTIFICADORGRUPOMTC varchar2(160),
                ASNN_IDENTIFICADOR varchar2(160),
                ASNC_TIPO_REPORTE varchar2(160),
                ASND_FECHA_REPORTE varchar2(160),
                ASNC_TIPO_TELEFONICO varchar2(160), 
                ASNV_NUMERO_LINEA varchar2(160),
                ASNV_MENSAJE_MTC varchar2(160),
                ASNC_ESTADO varchar2(160),
                ASND_FECHA_NOTIFICACION varchar2(160),
                ASNC_MOTIVO_NONOTIFICA varchar2(160),
                ASNC_TIPO_TITULAR varchar2(160),
                ASNC_TIPO_CONTRATO varchar2(160),
                ASNC_TIPO_DOCTITULAR varchar2(160),
                ASNV_NUM_DOCTITULAR varchar2(160),
                ASNV_NOMBRETITULAR varchar2(160),
                ASND_FEC_INICONTRATO varchar2(160),
                ASND_FEC_FINCONTRATO varchar2(160),
                ASNV_DIRECCION varchar2(160),
                ASNV_UBIGEO varchar2(160)
            )"
        ];
        $array_sql[] = [
            'sql' => "BEGIN
                INSERT INTO USRAES.TMP_NOTIFICACIONES_{$this->userIdentifier}
                (ASNV_IDENTIFICADORGRUPOMTC,ASNN_IDENTIFICADOR,ASNC_TIPO_TELEFONICO,
                    ASNV_NUMERO_LINEA,ASNC_ESTADO,ASND_FECHA_NOTIFICACION,ASNC_MOTIVO_NONOTIFICA)
                select  
                AA.ASNV_IDENTIFICADORGRUPOMTC,
                AA.ASNN_IDENTIFICADOR,
                AA.ASNC_TIPO_TELEFONICO,
                AA.ASNV_NUMERO_LINEA,
                BB.estado ASNC_ESTADO,
                CASE WHEN ESTADO='S' THEN TO_CHAR(BB.FECHA, 'DD/MM/YYYY') WHEN ESTADO='N' THEN '' END  ASND_FECHA_NOTIFICACION,
                BB.OBSERVACION ASNC_MOTIVO_NONOTIFICA 
                from USRAES.TMP_TXT_NOTIFI_{$this->userIdentifier} AA 
                LEFT JOIN (
                    select
                    msisdn,
                    case when estado in ('S','A','G','R') then 'S' WHEN estado IN ('D') THEN 'N' ELSE '' END estado,
                    sysdate FECHA,
                    case when estado IN ('S','A','G','R') THEN '' when upper(desc_serv) like '%PORT%OUT%' THEN '01'
                        WHEN estado in ('D') THEN '02'
                    ELSE '' END OBSERVACION  
                from (
                    select  SUBSCRIPTION_ACCESS_NUMBER msisdn,
                            agreement_status estado,
                            agreement_start_date fch_inio,
                            AGREEMENT_REASON_STATUS_DESC desc_serv,
                            row_number() over(partition by SUBSCRIPTION_ACCESS_NUMBER order by agreement_start_date desc) flag 
                    from DWA.DW_M_SUBSCRIPTION_HIST PARTITION (P_{$str_periodo})  -- <---- colocar el periodo de la fecha (202307--14).
                    where SUBSCRIPTION_ACCESS_NUMBER in 
                    (select '51'||to_char(to_number(regexp_replace(ASNV_NUMERO_LINEA, '[^0-9]+', ''))) ASNV_NUMERO_LINEA 
                    from USRAES.TMP_TXT_NOTIFI_{$this->userIdentifier})
                ) 
                where flag=1) BB 
                ON '51'||to_char(to_number(regexp_replace(AA.ASNV_NUMERO_LINEA, '[^0-9]+', '')))=BB.msisdn;
                COMMIT;
            END;"
        ];
        $this->exec_sql($array_sql);
    }

    public function getPlantilla(){
        return DB::table("USRAES.TMP_PLANTILLA_{$this->userIdentifier}")->select('ASNV_IDENTIFICADORGRUPOMTC',
        'ASNN_IDENTIFICADOR',
        'ASNC_TIPO_REPORTE',
        'ASND_FECHA_REPORTE',
        'ASNC_TIPO_TELEFONICO',
        'ASNV_NUMERO_LINEA',
        'ASNC_TIPO_SUSPENCION',
        'ASNN_NUM_DIAS_SUSPENDE',
        'ASNV_MENSAJE_MTC',
        'ASNC_ESTADO',
        DB::raw("to_char(ASND_FECHA_INI_SUSPENSION, 'dd/mm/yyyy') as ASND_FECHA_INI_SUSPENSION"),
        DB::raw("to_char(ASND_FECHA_FIN_SUSPENSION, 'dd/mm/yyyy') as ASND_FECHA_FIN_SUSPENSION"),
        'ASNC_FLAG_REACTIVADO',
        DB::raw("to_char(ASND_FECHA_REACTIVACION, 'dd/mm/yyyy') as ASND_FECHA_REACTIVACION"),
        'ASNC_MOTIVO_NOSUSPENSION',
        DB::raw("to_char(ASND_FECHA_BAJA, 'dd/mm/yyyy') as ASND_FECHA_BAJA"),
        'ASNC_TIPO_TITULAR',
        'ASNC_TIPO_CONTRATO',
        'ASNC_TIPO_DOCTITULAR',
        DB::raw("CASE WHEN ASNC_TIPO_DOCTITULAR='D' THEN LPAD(ASNV_NUM_DOCTITULAR, 8, '0') WHEN ASNC_TIPO_DOCTITULAR IN ('C','P') THEN LPAD(ASNV_NUM_DOCTITULAR, 12, '0') ELSE ASNV_NUM_DOCTITULAR END AS ASNV_NUM_DOCTITULAR"),
        'ASNV_NOMBRETITULAR',
        DB::raw("to_char(ASND_FEC_INICONTRATO, 'dd/mm/yyyy') as ASND_FEC_INICONTRATO"),
        DB::raw("to_char(ASND_FEC_FINCONTRATO, 'dd/mm/yyyy') as ASND_FEC_FINCONTRATO"),
        'ASNV_DIRECCION',
        'ASNV_UBIGEO')->get();
    }

    public function getCountSuspensionesByAsncEstado($estado)
    {
        $resp = DB::table("USRAES.TMP_PLANTILLA_SUS_{$this->userIdentifier}")
        ->select(DB::raw("count(*) as counter"))
        ->where("ASNC_ESTADO", $estado)
        ->get();
        return $resp[0]->counter;
    }

    public function getLineas()
    {
        return DB::table("USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier}")
        ->select(DB::raw('51||msisdn as lineas'),'modalidad')
        ->where('estado_subs', 'not like', 'D')
        ->get();
    }

    public function getLineasMoviles()
    {
        return DB::table("USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier}")
        ->select(DB::raw('\'51\'||MSISDN||\',TEXT,"A solicitud del MTC el trafico saliente de voz y datos de tu servicio sera suspendido por realizar comunicaciones malintencionadas"\' as mensaje'))
        ->where('estado_subs', 'not like', 'D')
        ->where(DB::raw("substr(msisdn,1,1)"), '9')
        ->get();
    }

    public function getLineasFijas()
    {
        return DB::table("USRAES.TMP_SUSPENSIONES_1_{$this->userIdentifier}")
        ->select(DB::raw("'51'||msisdn as acceptphone"))
        ->where('estado_subs', 'not like', 'D')
        ->where(DB::raw("substr(msisdn,1,1)"), '!=', '9')
        ->get();
    }

    public function getReporteTitularidad()
    {
        return DB::select(DB::raw("SELECT A.*,B.ASNC_TIPO_TITULAR ASNC_TIPO_TITULAR,B.ASNC_TIPO_CONTRATO   ASNC_TIPO_CONTRATO,B.ASNC_TIPO_DOCTITULAR   ASNC_TIPO_DOCTITULAR,
        B.ASNV_NUM_DOCTITULAR   ASNV_NUM_DOCTITULAR,
        B.ASNV_NOMBRETITULAR    ASNV_NOMBRETITULAR,
        TO_CHAR(B.ASND_FEC_INICONTRATO, 'dd/mm/yyyy')   ASND_FEC_INICONTRATO,
        TO_CHAR(B.ASND_FEC_FINCONTRATO, 'dd/mm/yyyy')  ASND_FEC_FINCONTRATO,
        B.ASNV_DIRECCION   ASNV_DIRECCION,
        B.ASNV_UBIGEO    ASNV_UBIGEO,
        NULL ASND_FECHAHORA_LLAMADA
        FROM USRAES.TMP_PLANTILLA_TITU_{$this->userIdentifier} A
        LEFT JOIN (SELECT * FROM USRAES.TMP_TITULARES_1_{$this->userIdentifier} WHERE ESTADO_SUBS NOT LIKE 'D' OR (ESTADO_SUBS LIKE 'D' AND AGREEMENT_REASON_STATUS_DESC LIKE '%PORT OUT%'))  B
        ON (B.MSISDN=TO_CHAR(TO_NUMBER(A.ASNV_NUMERO_LINEA)))"));
    }

    public function getCountRepTitularidadByAsncTipoTitular($tipo)
    {
        $resp = DB::select(DB::raw("SELECT
        count(*) as counter
        FROM USRAES.TMP_PLANTILLA_TITU_{$this->userIdentifier} A
        LEFT JOIN (SELECT * FROM USRAES.TMP_TITULARES_1_{$this->userIdentifier} WHERE ESTADO_SUBS NOT LIKE 'D' OR (ESTADO_SUBS LIKE 'D' AND AGREEMENT_REASON_STATUS_DESC LIKE '%PORT OUT%'))  B
        ON (B.MSISDN=TO_CHAR(TO_NUMBER(A.ASNV_NUMERO_LINEA)))
        WHERE B.ASNC_TIPO_TITULAR = :p_tipo_titular"), ['p_tipo_titular' => $tipo]);
        return $resp[0]->counter; 
    }

    public function getReporteMensual(){
        return DB::select(DB::raw("SELECT A.*,B.ASNC_TIPO_TITULAR ASNC_TIPO_TITULAR,B.ASNC_TIPO_CONTRATO ASNC_TIPO_CONTRATO,B.ASNC_TIPO_DOCTITULAR ASNC_TIPO_DOCTITULAR,
        B.ASNV_NUM_DOCTITULAR ASNV_NUM_DOCTITULAR,B.ASNV_NOMBRETITULAR  ASNV_NOMBRETITULAR,TO_CHAR(B.ASND_FEC_INICONTRATO, 'DD/MM/YYYY') ASND_FEC_INICONTRATO,
        TO_CHAR(B.ASND_FEC_FINCONTRATO, 'DD/MM/YYYY')  ASND_FEC_FINCONTRATO,B.ASNV_DIRECCION ASNV_DIRECCION,B.ASNV_UBIGEO  ASNV_UBIGEO,NULL  ASND_FECHAHORA_LLAMADA
        FROM USRAES.TMP_PLANTILLA_REPOMENSUAL_{$this->userIdentifier} A
        LEFT JOIN (SELECT * FROM USRAES.TMP_REPOMENSUAL_1_{$this->userIdentifier} WHERE ESTADO_SUBS NOT LIKE 'D')  B
        ON (B.MSISDN=A.ASNV_NUMERO_LINEA)"));
    }

    public function getReporteNotificacion(){
        return DB::table("USRAES.TMP_NOTIFICACIONES_{$this->userIdentifier}")->get();
    }

    private function exec_sql($sqls){
        foreach($sqls as $row){
            DB::statement(DB::Raw($row['sql']));
        }
    }
}
