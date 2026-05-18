<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Infrastructure\Repository;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\Adquisiciones\Domain\AdquisicionRepository;
use Illuminate\Support\Facades\DB;

class EloquentAdquisicionRepository implements AdquisicionRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getByImeis(array $imeis)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}(imei varchar2(100))");

        foreach($imeis as $value){
            DB::table("USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}")->insert(["imei" => $value]);
        }

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_IMEI_1_TEMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        $sql = "CREATE TABLE USRAES.DAPU_IMEI_1_TEMP_{$this->userIdentifier} AS
        SELECT /*+ PARALLEL(4)*/ DISTINCT  
        PEDID_FECHAENTREGA FECHA_ADQUISICION
	,SERIC_CODSERIE IMEI
	,NVL(DEPEV_NROTELEFONO, CLIEV_TELEFONOCLIENTE) MSISDN
	,TDOCV_DESCRIPCION TIPO_DOC
	,CL.CLIEV_NRODOCCLIENTE NUM_DOC
	,NVL(CL.CLIEV_RAZONSOCIAL, CL.CLIEV_NOMBRECLIENTE || ' ' || CL.CLIEV_PATERNOCLIENTE || ' ' || CL.CLIEV_MATERNOCLIENTE) CLIENTE
	,PEDIV_DESCTIPOOPERACION SEGMENTO
	,DEPEV_DESCRIPCIONLP DESCRIP_RAZON_VENTA
 	,PEDIV_DESCTIPOOPERACION PLAN_ADQUIRIDO
	,DEPEV_DESCMATERIAL MARCA
    	FROM DWS.SA_SSAPT_PEDIDO PED
    	JOIN DWS.SA_SSAPT_DETALLEPEDIDO DP ON DP.PEDIN_NROPEDIDO = PED.PEDIN_NROPEDIDO
	AND PED.PEDIC_ESTADO = 'PAG'
    	JOIN DWS.SA_SSAPT_MATERIAL M ON M.MATEC_CODMATERIAL = DP.DEPEC_CODMATERIAL
    	LEFT JOIN DWS.SA_SSAPT_CLIENTE CL ON CL.CLIEC_TIPODOCCLIENTE = PED.PEDIC_TIPODOCCLIENTE
    	AND CL.CLIEV_NRODOCCLIENTE = PED.PEDIV_NRODOCCLIENTE
    	LEFT JOIN DWS.SA_SSAPT_PAGO PAG ON PAG.PEDIN_NROPEDIDO = PED.PEDIN_NROPEDIDO
    	AND PAG.PAGOC_ESTADO = 'PAG'
    	LEFT JOIN DWS.SA_SSAPT_TIPODOCUMENTO TD ON TD.TDOCC_CODIGO = PED.PEDIC_TIPODOCCLIENTE
    	WHERE SUBSTR(TRIM(LEADING '0' FROM DP.SERIC_CODSERIE), 1, 14) IN (select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier})
    	UNION 
    	SELECT /*+ PARALLEL(8) */ DISTINCT
    	FECHA_VENTA FECHA_ADQUISCIION, 
    	IMEI, 
    	NRO_TELEFONO MSISDN, 
    	TIPO_DOC_CLIENTE TIPO_DOC,
    	CLIENTE NUM_DOC, 
    	NOMBRE_CLIENTE CLIENTE, 
    	DESC_TIPO_VENTA SEGMENTO,
    	DESC_CAMPANA DESCRIP_RAZON_VENTA, 
    	PT PLAN_ADQUIRIDO,
    	DES_EQUIPO MARCA
    	FROM DWA.DW_SELLOUT A
    	WHERE NRO_TELEFONO = '0'
    	AND SUBSTR(TRIM(LEADING '0' FROM A.IMEI), 1, 14) IN (select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier})";

        DB::statement($sql, []);

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_IMEI_2_TEMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        DB::statement("CREATE TABLE USRAES.DAPU_IMEI_2_TEMP_{$this->userIdentifier} AS
        SELECT DISTINCT
                TRUNC(A.SALE_DATE) FECHA_ADQUISICION,
                A.SERIAL_NUMBER IMEI,
                A.PRODUCT_NUMBER MSISDN,
                A.CUSTOMER_DOCUMENT_TYPE_DESC TIPO_DOC,
                A.ID_CARD_VALUE NUM_DOC,
                A.CUSTOMER_NAME CLIENTE,
                A.SALES_TYPE_DESC SEGMENTO,
                A.SALES_REASON_DESC DESCRIP_RAZON_VENTA,
                'NA' PLAN_ADQUIRIDO,
                A.PROD_OFFER_ITEM_NAME MARCA
        FROM DWA.DW_T_SALES A
        WHERE SUBSTR(TRIM(LEADING '0' FROM SERIAL_NUMBER),1,14) IN (select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier})
        AND SALES_REASON_DESC <>'PREACTIVACION'
        AND SALES_STATUS_DESC = 'PAGADO'
        UNION ALL 
        SELECT TRUNC(S.FECHA_VENTA) FECHA_ADQUISICION,
            S.IMEI IMEI,
            S.NRO_TELEFONO MSISDN,
            S.DESC_TIPO_DOC_CLIENTE TIPO_DOC,
            S.CLIENTE NUM_DOC,
            S.NOMBRE_CLIENTE CLIENTE,
            S.DESC_TIPO_VENTA SEGMENTO,
            UPPER(S.DESC_CLASE_VENTA) DESCRIP_RAZON_VENTA,
            s.PT PLAN_ADQUIRIDO,
            S.DES_EQUIPO MARCA
        FROM DM.DW_SELLOUT S
        WHERE 
        SUBSTR(TRIM(LEADING '0' FROM imei),1,14) IN (select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier})");

        /*select 
        s.FECHA_VENTA FECHA_ADQUISICION, 
        IMEI,
        s.NRO_TELEFONO msisdn,
        s.DESC_TIPO_DOC_CLIENTE tipo_doc,
        s.CLIENTE num_doc,
        s.NOMBRE_CLIENTE CLIENTE,
        s.DESC_TIPO_VENTA SEGMENTO,
        s.DESC_CLASE_VENTA descrip_razon_venta,
        s.PT PLAN_ADQUIRIDO,
        DES_EQUIPO MARCA
        from dm.dw_sellout s
        where 
        SUBSTR(TRIM(LEADING '0' FROM imei),1,14) IN (select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}) 
        AND s.TIPO_PRODUCTO = 'MOVIL'");*/

        DB::statement("BEGIN
            insert into USRAES.DAPU_IMEI_1_TEMP_{$this->userIdentifier}
            select * from USRAES.DAPU_IMEI_2_TEMP_{$this->userIdentifier}
            where imei not in (select imei from USRAES.DAPU_IMEI_1_TEMP_{$this->userIdentifier});
            COMMIT;
        END;");

        /*if (count($data) === 0) {
            $sql = "select 
            s.FECHA_VENTA FECHA_ADQUISICION, 
            IMEI, s.NRO_TELEFONO msisdn,s.DESC_TIPO_DOC_CLIENTE tipo_doc,
            s.CLIENTE num_doc, s.NOMBRE_CLIENTE CLIENTE,
            s.DESC_TIPO_VENTA SEGMENTO,s.DESC_CLASE_VENTA descrip_razon_venta,
            s.PT PLAN_ADQUIRIDO, DES_EQUIPO MARCA
            from dm.dw_sellout s
            where imei like '%'||?||'%' -- CAMBIAR IMEI
            AND s.TIPO_PRODUCTO = 'MOVIL'";
            $data = DB::select(DB::raw($sql), [$imei]);
        }*/
        $data = DB::select(DB::raw("SELECT 
        bb.FECHA_ADQUISICION,aa.imei,
        bb.msisdn,bb.tipo_doc,bb.Num_doc,Cliente,bb.Segmento,
        case 
            when bb.descrip_razon_venta is NULL then 'IMEI NO PERTENECE A CLARO' 
            when bb.descrip_razon_venta is not NULL then bb.descrip_razon_venta
        END descrip_razon_venta,
        bb.PLAN_ADQUIRIDO,bb.MARCA
        from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier} aa 
        left join USRAES.DAPU_IMEI_1_TEMP_{$this->userIdentifier} bb 
        on SUBSTR(REGEXP_REPLACE(aa.imei, '^0+', ''),1,14) = SUBSTR(REGEXP_REPLACE(bb.imei, '^0+', ''),1,14)"));
        return $data;
    }
}


