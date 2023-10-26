<?php

namespace AMovil\Reports\RepDetConsumoNF\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\RepDetConsumoNF\Domain\DetalleConsumoNFRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class EloquentDetalleConsumoNFRepository implements DetalleConsumoNFRepository
{
    private $connection;
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getReporteDetallado(string $numCuenta, DateTime $fechaIni, DateTime $fechaFin)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $ciclo = $this->getCiclo($numCuenta);

        if($ciclo === null){
            throw new Exception("No se control un ciclo para la cuenta '{$numCuenta}'");
        }

        $dtPartition = new DateTime();

        if($ciclo !== "01"){
            $dtPartition->modify("-1 month");
        }

        $strMonthPartition = $dtPartition->format("Ym");

        $this->connection = "oracle";

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}(
                NUMERO_CUENTA_LARGA VARCHAR2(255),
                CICLO VARCHAR2(10),
                SUBSCRIPTION_ACCESS_NUMBER VARCHAR2(20),
                MSISDN VARCHAR2(20),
                AGREEMENT_STATUS VARCHAR2(50)
            )"
        ];
        $queries[] = [
            "sql" => "BEGIN
                INSERT INTO USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}
                SELECT /*+ PARALLEL(4)*/ CUSTOMER_ACCOUNT_DESC numero_cuenta_larga,
                CUSTOMER_ACCOUNT_BILLING_CYCLE_SC ciclo,
                SUBSCRIPTION_ACCESS_NUMBER,
                SUBSTR(SUBSCRIPTION_ACCESS_NUMBER,3,9) MSISDN,
                AGREEMENT_STATUS 
                FROM  DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$strMonthPartition})
                WHERE CUSTOMER_ACCOUNT_DESC = :p_num_cuenta AND AGREEMENT_STATUS <>'D';
                COMMIT;
            END;",
            "params" => ["p_num_cuenta" => $numCuenta]
        ];

        $this->exec_sql($queries);

        $lineas = DB::connection($this->connection)->table("USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}")->get();
        $this->connection = "oracle_reptdm";
        
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}(
                NUMERO_CUENTA_LARGA VARCHAR2(255),
                CICLO VARCHAR2(10),
                SUBSCRIPTION_ACCESS_NUMBER VARCHAR2(20),
                MSISDN VARCHAR2(20),
                AGREEMENT_STATUS VARCHAR2(50)
            )"
        ];

        $this->exec_sql($queries);

        foreach($lineas as $row){
            DB::connection($this->connection)
            ->table("USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}")
            ->insert([
                "numero_cuenta_larga" => $row->numero_cuenta_larga,
                "ciclo" => $row->ciclo,
                "subscription_access_number" => $row->subscription_access_number,
                "msisdn" => $row->msisdn,
                "agreement_status" => $row->agreement_status,
            ]);
        }
        $lineas = [];

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.REP_LLAM_SMS_TEMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.REP_LLAM_SMS_TEMP_{$this->userIdentifier}(
        NRO_TEL_ORIGEN VARCHAR2(50),
        DIA DATE,
        HORA_INICIO VARCHAR2(8),
        HORA_FIN VARCHAR2(8),
        NRO_TEL_DESTINO VARCHAR2(100),
        CONSUMO VARCHAR2(50),
        TIPO_SERVICIO VARCHAR2(50),
        TIPO_LLAMADA VARCHAR2(50),
        TIPO_DESTINO VARCHAR2(50),
        MODO_DESTINO VARCHAR2(50),
        OPERADOR_DESTINO VARCHAR2(100),
        CLASE_DESTINO VARCHAR2(50)
        )"];
        
        $fechaRecorrido = clone $fechaIni;

        while ($fechaRecorrido->format("Ymd") <= $fechaFin->format("Ymd")) {
            $strDay = $fechaRecorrido->format("Ymd");
            $queries[] = [
                "sql" => "BEGIN
                INSERT INTO USRAES.REP_LLAM_SMS_TEMP_{$this->userIdentifier}
                SELECT A.TIM_NUMBER NRO_TEL_ORIGEN, TRUNC(TO_DATE(A.CHARGING_START_TIME,'YYYYMMDDHH24MISS')) DIA,
                TO_CHAR(TO_DATE(A.CHARGING_START_TIME,'YYYYMMDDHH24MISS'),'HH24:MI:SS') HORA_INICIO,
                TO_CHAR(TO_DATE(A.CHARGING_END_TIME,'YYYYMMDDHH24MISS'),'HH24:MI:SS') HORA_FIN,
                A.NUMBER_B NRO_TEL_DESTINO,
                CASE WHEN A.RECORD_TYPE IN ('01','03') THEN TO_CHAR(TRUNC(A.CALL_DURATION/3600),'FM00')
                        ||':'||TO_CHAR(TRUNC(MOD(A.CALL_DURATION,3600)/60),'FM00')
                        ||':'||TO_CHAR(MOD(A.CALL_DURATION,60),'FM00')
                    WHEN A.RECORD_TYPE IN ('08') THEN A.CALL_DURATION END CONSUMO,
                CASE WHEN A.RECORD_TYPE IN ('01','03') THEN 'VOZ'
                    WHEN A.RECORD_TYPE IN ('08') THEN 'SMS' END TIPO_SERVICIO,
                'Saliente' TIPO_LLAMADA,
                CASE WHEN A.OPERADOR_B IS NULL AND A.ZONA_OPE_B IS NULL AND A.CODIGODEPAIS IS NOT NULL THEN 'LDI'
                    WHEN UPPER(B.SENTIDO) = 'LLAMADA SALIENTE' AND NVL(A.RN_DES, '00') = '21' AND A.OPERADOR_B IS NOT NULL AND A.ZONA_OPE_B IS NULL
                        AND A.CODIGODEPAIS IS NOT NULL THEN 'LDI'
                    WHEN UPPER(B.SENTIDO) = 'LLAMADA SALIENTE' AND NVL(A.RN_DES, '00') NOT IN ('21', '00') AND A.OPERADOR_B IS NOT NULL
                        AND A.ZONA_OPE_B IS NULL AND A.CODIGODEPAIS IS NOT NULL THEN 'LDI'
                    WHEN UPPER(B.SENTIDO) = 'LLAMADA SALIENTE' AND NVL(A.RN_DES, '00') = '21' AND A.OPERADOR_B IS NOT NULL AND A.ZONA_OPE_B IS NOT NULL
                        AND A.CODIGODEPAIS IS NOT NULL THEN 'LDI'
                    WHEN UPPER(B.SENTIDO) = 'LLAMADA SALIENTE' AND NVL(A.RN_DES, '00') NOT IN ('21', '00') AND A.OPERADOR_B IS NOT NULL
                        AND A.ZONA_OPE_B IS NOT NULL AND A.CODIGODEPAIS IS NOT NULL THEN 'LDI'
                    ELSE 'Local' END TIPO_DESTINO,
                CASE WHEN (UPPER(B.SENTIDO) = 'LLAMADA SALIENTE' OR UPPER(B.SENTIDO) = 'MENSAJE SALIENTE')
                        AND NVL(A.RN_DES, '00') IN ('78', '21') THEN 'OnNet'
                    WHEN NVL(A.RN_DES, '00') = '00' THEN 'Otros'
                    ELSE 'OffNet' END MODO_DESTINO,
                UPPER(CASE WHEN UPPER(B.SENTIDO) = 'LLAMADA SALIENTE' AND (A.RN_DES NOT IN ('00','64','29') AND A.RN_DES IS NOT NULL) THEN DS.OPERADOR_2
                        ELSE 'NO ESPECIFICADO' END) OPERADOR_DESTINO,
                case when length(number_b) = 11 and number_b like '519%' and number_b not in ('51998251574', '51998252047') then 'MOVIL'
                    else case when length(number_b) = 10 and number_b like '51%' then 'FIJO'
                        else case when length(number_b) = 9 and nvl(rn_ori, '00') <> '00' and number_b like '9%' then 'MOVIL'
                            else case when length(number_b) = 8 and nvl(rn_ori, '00') <> '00' then 'FIJO'
                            else case when length(number_b) = 7 and nvl(rn_ori, '00') <> '00' then 'FIJO'
                                ELSE case when (length(A.number_b) = 1 OR length(A.number_b) = 2 OR length(A.number_b) = 3 OR length(A.number_b) = 4 OR
                                        length(A.number_b) = 5 OR length(A.number_b) = 6 OR length(A.number_b) = 7 OR length(A.number_b) = 8) then 'CORTO'
                                        WHEN A.operador_b is null and A.zona_ope_b is null and A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA ENTRANTE' and nvl(A.rn_ori, '00') = '21' and A.operador_b is not null and A.zona_ope_b is null and
                                        A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA ENTRANTE' and nvl(A.rn_ori, '00') not in ('21', '00') and A.operador_b is not null and
                                        A.zona_ope_b is null and A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA ENTRANTE' and nvl(A.rn_ori, '00') = '21' and A.operador_b is not null and A.zona_ope_b is not null and
                                        A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA ENTRANTE' and nvl(A.rn_ori, '00') not in ('21', '00') and A.operador_b is not null and
                                        A.zona_ope_b is not null and A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA SALIENTE' and nvl(A.rn_des, '00') = '21' and A.operador_b is not null and A.zona_ope_b is null and
                                        A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA SALIENTE' and nvl(A.rn_des, '00') not in ('21', '00') and A.operador_b is not null and
                                        A.zona_ope_b is null and A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA SALIENTE' and nvl(A.rn_des, '00') = '21' and A.operador_b is not null and A.zona_ope_b is not null and
                                        A.codigodepais is not null THEN 'LDI'
                                        WHEN upper(b.sentido)='LLAMADA SALIENTE' and nvl(A.rn_des, '00') not in ('21', '00') and A.operador_b is not null and
                                        A.zona_ope_b is not null and A.codigodepais is not null THEN 'LDI'
                                        ELSE 'OTROS'
                                END
                            end
                            end
                        end
                        end
                    end CLASE_DESTINO
                FROM DM.CDR_DWH PARTITION (CDR_DWH_{$strDay}) A -- PARTITION DEL DIA CDR_DWH_20231022
                LEFT JOIN USRAES.CDR_TIPO_CDR B ON A.RECORD_TYPE = B.RECORD_TYPE
                LEFT JOIN USRAES.RTM_OPERADORES DS ON A.RN_DES = DS.RN
                WHERE A.CALL_DURATION > 0
                    AND LENGTH(A.TIM_NUMBER) = 11
                    AND A.RECORD_TYPE IN ('01','03','08') -- 01 y 03: Llamada Saliente - 08: SMS Saliente
                    -- Nota: algunas llamadas con record_type '12' (PSTN-terminated call-Llamada Entrante) coinciden con salientes de la fuente TFI
                    --       por ello no se debe incluir aqui
                    AND A.TIM_NUMBER IN (SELECT SUBSCRIPTION_ACCESS_NUMBER FROM USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier} GROUP BY SUBSCRIPTION_ACCESS_NUMBER)
                UNION ALL
                SELECT T.ANI NRO_TEL_ORIGEN, TRUNC(T.FECHAINICIO) DIA,
                TO_CHAR(T.FECHAINICIO, 'HH24:MI:SS') HORA_INICIO, TO_CHAR(T.FECHAFIN, 'HH24:MI:SS') HORA_FIN,
                T.DNI NRO_TEL_DESTINO,
                TO_CHAR(TRUNC(T.DURACION/3600),'FM00')||':'||TO_CHAR(TRUNC(MOD(T.DURACION,3600)/60),'FM00')
                ||':'||TO_CHAR(MOD(T.DURACION,60),'FM00') CONSUMO, --case when idcarriertermina = 1 then 'TELEFONICA' END OPERADORA_DESTINO,
                'VOZ' TIPO_SERVICIO, 'Saliente' TIPO_LLAMADA,
                CASE WHEN LENGTH(T.DNI) = 9 AND T.DNI LIKE '9%' THEN 'Local'
                            WHEN T.DNICENTRAL LIKE '1912%' THEN 'LDI'
                            WHEN LENGTH(DNI) < 9  AND UPPER(T.TIPO_LLAMADA) IN ('LOCAL', 'NACIONAL') THEN 'Local'
                            WHEN UPPER(T.TIPO_LLAMADA) = 'INTERNACIONAL' THEN 'LDI'
                    ELSE 'Otros' END TIPO_DESTINO,
                CASE WHEN LENGTH(T.DNI) = 9 AND T.DNI LIKE '9%' AND UPPER(G.DESCRIPCION) ='CLARO' THEN 'OnNet'
                    WHEN LENGTH(T.DNI) = 9 AND T.DNI LIKE '9%' AND UPPER(G.DESCRIPCION) NOT IN ('CLARO') THEN 'OffNet'
                    WHEN LENGTH(T.DNI) = 9 AND T.DNI LIKE '9%' AND UPPER(G.DESCRIPCION) IS NULL THEN 'OffNet'
                    WHEN T.DNICENTRAL LIKE '1912%' AND UPPER(T.TIPO_LLAMADA) = 'INTERNACIONAL' AND UPPER(G.DESCRIPCION) IN ('CLARO','TELMEX PERU','TELMEX S.A.') THEN 'OnNet'
                    WHEN T.DNICENTRAL LIKE '1912%' AND UPPER(T.TIPO_LLAMADA) = 'INTERNACIONAL' AND UPPER(G.DESCRIPCION) NOT IN ('CLARO','TELMEX PERU','TELMEX S.A.') THEN 'OffNet'
                    WHEN UPPER(T.TIPO_LLAMADA) = 'INTERNACIONAL' AND UPPER(G.DESCRIPCION) IN ('CLARO','TELMEX PERU','TELMEX S.A.') THEN 'OnNet'
                    WHEN UPPER(T.TIPO_LLAMADA) = 'INTERNACIONAL' AND UPPER(G.DESCRIPCION) NOT IN ('CLARO','TELMEX PERU','TELMEX S.A.') THEN 'OffNet'
                    WHEN LENGTH(T.DNI)<9 AND UPPER(T.TIPO_LLAMADA) IN ('LOCAL','NACIONAL') AND UPPER(G.DESCRIPCION) IN ('CLARO','TELMEX PERU','TELMEX S.A.') THEN 'OnNet'
                    WHEN LENGTH(T.DNI)<9 AND UPPER(T.TIPO_LLAMADA) IN ('LOCAL','NACIONAL') AND UPPER(G.DESCRIPCION) NOT IN ('CLARO','TELMEX PERU','TELMEX S.A.') THEN 'OffNet'
                    WHEN UPPER(T.TIPO_LLAMADA) IN ('LOCAL','NACIONAL') AND G.DESCRIPCION IS NULL THEN 'OffNet'
                    ELSE 'OTROS' END MODO_DESTINO,
                UPPER(CASE WHEN G.DESCRIPCION IS NULL THEN 'NO ESPECIFICADO'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%TELEFONICA%' THEN 'MOVISTAR'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%VIET%' THEN 'BITEL'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%VIR%' THEN 'VIRGIN'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%TELM%' THEN 'CLARO'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%WIN%' THEN 'WINNER'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%OPTICAL NET%' THEN 'OPTICAL NETWORKS'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%OPTICAL TEC%' THEN 'OPTICAL TECHNOLOGIES'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%FRAV%' THEN 'FRAVATEL'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%AMITEL%' THEN 'AMITEL'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%ANURA%' THEN 'ANURA'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%GILAT%' THEN 'GILAT'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%NETLINE%' THEN 'NETLINE'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%IDT%' THEN 'IDT'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%INFODUCTOS%' THEN 'INFODUCTOS'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%SITEL%' THEN 'SITEL'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%INGENIERÍA%' THEN 'INGENIERÍA EN GESTIÓN DE NEGOCIOS Y OPORTUNIDADES'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%INVERSIONES PERUANAS%' THEN 'INVERSIONES PERUANAS EN TELECOMUNICACIONES'
                            WHEN UPPER(G.DESCRIPCION) LIKE '%CONSORCIO OPTICAL%' THEN 'CONSORCIO OPTICAL'
                            ELSE G.DESCRIPCION END) OPERADOR_DESTINO,
                    UPPER(CASE WHEN LENGTH(T.DNI)=9 AND T.DNI LIKE '9%' THEN 'MOVIL'
                            WHEN T.DNICENTRAL LIKE '1912%' THEN 'LDI'
                            WHEN LENGTH(T.DNI)<9 AND UPPER(T.TIPO_LLAMADA) IN ('LOCAL','NACIONAL') THEN 'FIJO'
                            WHEN LENGTH(T.DNI)>=9 AND UPPER(T.TIPO_LLAMADA) IN ('LOCAL','NACIONAL') AND G.DESCRIPCION IS NULL THEN 'MOVIL'
                            WHEN UPPER(T.TIPO_LLAMADA)='INTERNACIONAL' THEN 'LDI'
                        ELSE 'OTROS' END) CLASE_DESTINO
                FROM DM.CDR_TFI PARTITION (CDR_TFI_{$strDay}) T --PARTITION DEL DIA CDR_TFI_20231022
                LEFT JOIN USRAES.DE_CARRIER G ON T.IDCARRIERTERMINA = G.IDCARRIER
                WHERE LENGTH(T.ANI) = 9
                      AND T.ORIENTACION = 'Originated'
                      AND T.DURACION > 0 --and t.dni in ('975182333','956254793','997991801','943720790') --4 cdrs q el facturado no contabiliza
                      AND T.ANI IN (SELECT MSISDN FROM USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier} GROUP BY MSISDN);
                COMMIT;
                END;"
            ];
            $fechaRecorrido->modify("+1 day");
        }

        $this->exec_sql($queries);

        $query = "SELECT A.numero_cuenta_larga,A.ciclo,'' NRO_FACTURA,B.NRO_TEL_ORIGEN,TO_CHAR(B.DIA, 'DD/MM/YYYY') DIA,B.HORA_INICIO,B.HORA_FIN,
        '' PAIS,B.NRO_TEL_DESTINO,
        B.CONSUMO,B.TIPO_SERVICIO,B.MODO_DESTINO||' - '||B.CLASE_DESTINO DESTINO,B.OPERADOR_DESTINO OPERADOR,B.TIPO_LLAMADA
        FROM (
            SELECT NUMERO_CUENTA_LARGA, CICLO FROM USRAES.REPORTE_LINEAS_TEMP_{$this->userIdentifier}
            GROUP BY NUMERO_CUENTA_LARGA, CICLO
        ) A
        INNER JOIN USRAES.REP_LLAM_SMS_TEMP_{$this->userIdentifier} B
        ON 1=1";

        return DB::connection($this->connection)->select(DB::raw($query));
    }

    public function getCiclo(string $numCuenta){
        $dtPartition = new DateTime();
        $strMonthPartition = $dtPartition->format("Ym");
        $query = "SELECT CUSTOMER_ACCOUNT_BILLING_CYCLE_SC ciclo from DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$strMonthPartition}) 
        WHERE CUSTOMER_ACCOUNT_DESC= :num_cuenta group by CUSTOMER_ACCOUNT_BILLING_CYCLE_SC";
        $result = DB::select(DB::raw($query), ["num_cuenta" => $numCuenta]);
        if(count($result) > 0){
            return $result[0]->ciclo;
        }
        return null;
    }

    private function exec_sql(array $plsql)
    {
        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
        }
    }
}
