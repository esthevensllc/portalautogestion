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
        VEPR_FECHA_REG FECHA_ADQUISICION,
        DVPR_SERIE IMEI,
        (SELECT DISTINCT SUBSTR(TRIM(VV.DVPR_LINEA),-9)
        FROM DWS.SA_SISACT_VENTA_PREPAGO PP
        INNER JOIN DWS.SA_SISACT_DETALLE_VENTA_PREPAGO VV ON VV.DVPR_ID = PP.VEPR_ID
        INNER JOIN DWS.SA_SISACT_VENTA_PREPAGO_DATOS DD ON DD.VEPR_ID = PP.VEPR_ID
        INNER JOIN DWS.SA_SSAPT_PEDIDO TT ON TT.PEDIN_NROPEDIDO = DD.VEPR_PEDIDO_SINERGIA 
        WHERE PP.VEPR_ESTADO = 'P' -- Que la Cabecera de la Venta sea Pagado
            AND VV.DVPR_ESTADO = 'P' -- Que su Detalle del item sea Pagado
            AND VV.DVPT_TIPO_ITEM = 'C' -- Que tenga Item Equipo
            AND VV.DVPR_COD_PROD_PREP = '01' -- Sólo Móvil Prepago
            AND PP.VEPR_TIPO_OPERACION = '01' -- Altas y Porta
            AND DD.VEPR_FECHA_PAGO IS NOT NULL
            AND DD.VEPR_PEDIDO_SINERGIA = P.VEPR_PEDIDO_SINERGIA
            AND VV.DVPR_NU_SECU= V.DVPR_NU_SECU) msisdn,
            M.TDOCV_DESCRIPCION tipo_doc,
            P.VEPR_NUM_DOC num_doc,
            NVL(TRIM(P.VEPR_NOM_CLIE||' '||P.VEPR_APE_CLIE), P.VEPR_RAZ_SOCI) CLIENTE,
            'PREPAGO' SEGMENTO,
            DECODE(P.VEPR_TIPO_DOCU,'ALT','ALTA','POR','PORTABILIDAD',P.VEPR_TIPO_DOCU) descrip_razon_venta,
            DVPR_DES_PLAN PLAN_ADQUIRIDO,
            V.DVPR_DESC_MATERIAL MARCA
        FROM DWS.SA_SISACT_VENTA_PREPAGO P
        INNER JOIN DWS.SA_SISACT_DETALLE_VENTA_PREPAGO V ON V.DVPR_ID = P.VEPR_ID
        LEFT JOIN DWS.SA_SECT_TIPO_DOCUMENTO M ON P.VEPR_TIPO_DOC = M.TDOCC_CODIGO -- TIPO DE DOC CLI 
        WHERE VEPR_ESTADO <> 'B'
            AND P.VEPR_ESTADO = 'P' -- Que la Cabecera de la Venta sea Pagado
            AND V.DVPR_ESTADO = 'P' -- Que su Detalle del item sea Pagado
            AND SUBSTR(TRIM(LEADING '0' FROM V.DVPR_SERIE),1,14) IN (
                select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}
            )
            AND V.DVPT_TIPO_ITEM = 'E'
            AND V.DVPR_COD_PROD_PREP = '01'

        UNION ALL

        SELECT FECHA_REGISTRO FECHA_ADQUISICION,
            EQUIPO_SERIE IMEI,
            TELEFONO msisdn,
            TD.TDOCV_DESCRIPCION tipo_doc, 
            DOC_CLIE_NUMERO num_doc, 
            NVL(TRIM(TITULAR_NOMBRE||' '||TITULAR_APELLIDO), TITULAR_RAZON_SOCIAL) CLIENTE, 
            'PREPAGO' SEGMENTO,
            TIPO_REPOSICION_DES descrip_razon_venta,
            PLAN_TARIFARIO_DES PLAN_ADQUIRIDO,
            EQUIPO_MATERIAL_DES MARCA
        FROM DWS.SA_SISACT_VENTA_REPO_PRE A
        INNER JOIN DWS.SA_SSAPT_PEDIDO B ON A.DOCUMENTO_SAP = TO_CHAR(B.PEDIN_NROPEDIDO)
        LEFT JOIN DWS.SA_SECT_TIPO_DOCUMENTO TD ON TD.TDOCC_CODIGO = A.DOC_CLIE_TIPO
        WHERE
            SUBSTR(TRIM(LEADING '0' FROM EQUIPO_SERIE),1,14) IN (
                select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}
            )
            AND A.ESTADO_REGISTRO IN ('PAGADO')
            AND B.PEDIC_ESTADO = 'PAG'
            AND A.TIPO_REPOSICION_COD = '33'
            
        UNION ALL

        SELECT C.CONTD_FECHA_CONTRATO FECHA_ADQUISICION,SERIE_EQUIPO IMEI,TELEFONO msisdn, 
            TD.TDOCV_DESCRIPCION tipo_doc,CONTV_NRO_DOC_CLIENTE num_doc,
            NVL(TRIM(CONTV_NOMBRE||' '||CONTV_APE_PAT||' '||CONTV_APE_MAT),CONTV_RAZONSOCIAL)
            AS CLIENTE,'POSTPAGO' SEGMENTO,TOP.TOPEV_DESCRIPCION descrip_razon_venta,
            D.PLAN_TARIFAR_DESC PLAN_ADQUIRIDO,DES_EQUIPO MARCA
        FROM DWS.SA_SISACT_AP_CONTRATO_DET D
        INNER JOIN DWS.SA_SISACT_AP_CONTRATO C ON C.CONTN_NUMERO_CONTRATO = D.ID_CONTRATO
        INNER JOIN DWS.SA_SISACT_INFO_VENTA_SAP S ON C.CONTN_NUMERO_CONTRATO = S.ID_CONTRATO 
            AND S.TIPO_DOCUMENTO = 'F'
            AND S.RECIBO = D.RECIBO
        INNER JOIN DWS.SA_SSAPT_PEDIDO P ON P.PEDIN_NROPEDIDO = S.NRO_DOCUMENTO
        LEFT JOIN DWS.SA_SISACT_AP_PRODUCTO PR ON PR.PRDC_CODIGO = C.CONTC_TIPO_PRODUCTO
        LEFT JOIN DWS.SA_SISACT_AP_TIPO_OPERACION TOP ON TOP.TOPEN_CODIGO = C.CONTC_TIPO_OPERACION 
            AND TOP.TPROC_CODIGO = C.CONTC_TIPO_PRODUCTO
        LEFT JOIN DWS.SA_SECT_TIPO_DOCUMENTO TD ON TD.TDOCC_CODIGO = C.CONTC_TIPO_DOC_CLIENTE
        WHERE
            SUBSTR(TRIM(LEADING '0' FROM D.SERIE_EQUIPO),1,14) IN (
                select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}
            )
            AND P.PEDIC_ESTADO = 'PAG' 
            AND C.CONTC_TIPO_PRODUCTO IN ('01') 
            AND ((C.CONTC_TIPO_OPERACION IN ('01','02')
                    AND C.CONTC_ESTADO = '8')
                OR (C.CONTC_TIPO_OPERACION IN ('04') 
                    AND C.CONTC_ESTADO = '7')) 

        UNION ALL

        SELECT V.FECHA_VENTA FECHA_ADQUISICION, DP.SERIC_CODSERIE IMEI, ' ' msisdn,
            TD.TDOCV_DESCRIPCION tipo_doc, 
            PED.PEDIV_NRODOCCLIENTE num_doc,
            NVL(TRIM(CL.CLIEV_NOMBRECLIENTE||' '||CLIEV_PATERNOCLIENTE||' ' ||CLIEV_MATERNOCLIENTE), 
                CLIEV_RAZONSOCIAL) AS CLIENTE,
                '  ' SEGMENTO,
            'EQUIPOS LIBERADOS' descrip_razon_venta,
            ' ' PLAN_ADQUIRIDO,
            DP.DEPEV_DESCMATERIAL MARCA
        FROM DWS.SA_SSAPT_PEDIDO PED
        JOIN DWS.SA_SSAPT_DETALLEPEDIDO DP ON DP.PEDIN_NROPEDIDO = PED.PEDIN_NROPEDIDO
        JOIN DWS.SA_SSAPT_MATERIAL M ON M.MATEC_CODMATERIAL = DP.DEPEC_CODMATERIAL
        LEFT JOIN DWS.SA_SSAPT_CLIENTE CL ON CL.CLIEC_TIPODOCCLIENTE = PED.PEDIC_TIPODOCCLIENTE
            AND CL.CLIEV_NRODOCCLIENTE = PED.PEDIV_NRODOCCLIENTE
        LEFT JOIN DWS.SA_SSAPT_PAGO PAG ON PAG.PEDIN_NROPEDIDO = PED.PEDIN_NROPEDIDO
            AND PAG.PAGOC_ESTADO = 'PAG' -- Por casos donde un Pedido tiene varios pagos. Ejm: 108339676
        LEFT JOIN DWS.SA_SSAPT_TIPODOCUMENTO TD ON TD.TDOCC_CODIGO = PED.PEDIC_TIPODOCCLIENTE
        LEFT JOIN DWS.SA_SISACT_INFO_VENTA_SAP SAP ON SAP.NRO_DOCUMENTO = TO_CHAR(PED.PEDIN_NROPEDIDO) 
            AND SAP.TIPO_DOCUMENTO = 'F'
        LEFT JOIN DWS.SA_SISACT_AP_VENTA V ON V.ID_DOCUMENTO = SAP.ID_VENTA
        LEFT JOIN DWS.SA_SISACT_AP_VENTA_DETALLE VD ON VD.ID_DOCUMENTO = V.ID_DOCUMENTO
        --LEFT JOIN DWS.SA_SECT_TIPO_DOCUMENTO TD ON TD.TDOCC_CODIGO = V.TIPO_DOC_CLIENTE
        WHERE
            SUBSTR(TRIM(LEADING '0' FROM DP.SERIC_CODSERIE),1,14) IN (
                select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}
            )
            AND PED.PEDIC_ESTADO = 'PAG'
            AND PED.PEDIC_CODTIPOOPERACION = '25'
            AND M.MATEC_TIPOMATERIAL = 'TV0003'";

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


