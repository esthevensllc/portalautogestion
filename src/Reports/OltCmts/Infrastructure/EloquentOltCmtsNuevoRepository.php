<?php

namespace AMovil\Reports\OltCmts\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\OltCmts\Domain\OltCmtsNuevoRepository;
use DateTime;
use Exception;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EloquentOltCmtsNuevoRepository implements OltCmtsNuevoRepository
{
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function getReportTypes()
    {
        $data = [
            ["id" => "1", "label" => "OLT"],
            ["id" => "2", "label" => "CMTS"],
        ];
        return json_decode(json_encode($data), false);
    }

    public function getOltsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT devicedisplayname id, devicedisplayname label
        FROM fija_analisis.fija_clientes_ftth
        WHERE tecnologia = 'FTTH' and fecha = toDate('{$stFecha}') and devicedisplayname is not null
        group by 1
        order by devicedisplayname";
        $data = DB::connection("ch-dn09")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getCmtsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT
        devicedisplayname id, devicedisplayname label
        from fija_analisis.fija_clientes_hfc
        where tecnologia='HFC' and fecha = toDate('{$stFecha}') and devicedisplayname is not null
        group by 1
        order by devicedisplayname";
        $data = DB::connection("ch-dn09")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getOltFinalReport(array $tickets)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return DB::connection("oracle")
        ->select(DB::raw("SELECT CUSTOMER_ID_CODCLI
        ,FUENTE
        ,SERIALNUMBER
        ,NOMBRE
        ,ID_CARD_TYPE_VALUE
        ,TELEFONO_CLARO
        ,NUMERO_ADICIONAL
        ,SERVICIO_PRODUCTO
        ,CORREO
        ,DISTRITO
        ,PROVINCIA
        ,DEPARTAMENTO
        ,DESCRIPCION_PRODUCTO
        ,AGREEMENT_STATUS
        ,AGREEMENT_STATUS_DATE
        ,AGREEMENT_START_DATE
        ,AGREEMENT_END_DATE
        ,INSTALLATION_MAP
        ,NUMERO
        ,DNI_RUC
        FROM
        (
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier} x
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Telefonia Fija'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
            AND DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier} x
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Acceso Dedicado a Internet'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
            AND DESCRIPCION_PRODUCTO LIKE '%MBPS%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier} x
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND TRIM(FAMILIA)='Cable'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
            AND DESCRIPCION_PRODUCTO LIKE '%TV%'
        )
        WHERE ORDEN=1"), ["p1" => $tickets[0], "p2" => $tickets[1], "p3" => $tickets[2]]);
    }

    public function getCmtsFinalReport(array $tickets)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return DB::connection("oracle")
        ->select(DB::raw("SELECT CUSTOMER_ID_CODCLI
        ,FUENTE
        ,SERIALNUMBER
        ,NOMBRE
        ,ID_CARD_TYPE_VALUE
        ,TELEFONO_CLARO
        ,NUMERO_ADICIONAL
        ,SERVICIO_PRODUCTO
        ,CORREO
        ,DISTRITO
        ,PROVINCIA
        ,DEPARTAMENTO
        ,DESCRIPCION_PRODUCTO
        ,AGREEMENT_STATUS
        ,AGREEMENT_STATUS_DATE
        ,AGREEMENT_START_DATE
        ,AGREEMENT_END_DATE
        ,INSTALLATION_MAP
        ,NUMERO
        ,DNI_RUC
        FROM
        (
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.cmts_table_final_{$this->userIdentifier} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Telefonia Fija'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
            AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD')<=FEC_INI_INCIDENCIA AND DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.cmts_table_final_{$this->userIdentifier} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Acceso Dedicado a Internet'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
            AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD')<=FEC_INI_INCIDENCIA AND DESCRIPCION_PRODUCTO LIKE '%MBPS%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY X.DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.cmts_table_final_{$this->userIdentifier} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN  (:p1, :p2, :p3)
                AND TRIM(FAMILIA)='Cable'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
            AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD')<=FEC_INI_INCIDENCIA AND DESCRIPCION_PRODUCTO LIKE '%TV%'
        )
        WHERE ORDEN=1"), ["p1" => $tickets[0], "p2" => $tickets[1], "p3" => $tickets[2]]);
    }


    public function getFilteredFinalReport(int $typeId, DateTime $fecha, array $values)
    {
        if (!in_array($typeId, [1, 2], true)) {
            throw new Exception("El tipo de reporte no es valido");
        }

        if (count($values) === 0) {
            throw new Exception("Debe seleccionar al menos un valor OLT/CMTS");
        }

        $this->userIdentifier = $this->sanitizeOracleSuffix($this->authService->getUserIdentifier());
        $this->cleanupTemporaryTables($this->userIdentifier);
        $this->generateReport($typeId, $fecha, $values);
        $this->rebuildSourceValidFilterTable($typeId);
        $this->rebuildCodcliTemporaryTable();
        $this->rebuildDevolucionTemporaryTable($typeId, $fecha);

        if ($typeId === 1) {
            return $this->getOltFilteredFinalReport();
        }

        return $this->getCmtsFilteredFinalReport();
    }

    public function cleanupTemporaryTables(?string $userIdentifier = null)
    {
        $suffix = $this->sanitizeOracleSuffix($userIdentifier ?: $this->authService->getUserIdentifier());

        $tables = [
            'TMP_BASE_FIJA_DESACTIVOS_OLT_CMTS',
            'TMP_SA_DEVOL_CASO1_OLT_CMTS',
            'TMP_DEVINSTXPROD_OLT_CMTS',
            'WRK_DEVOLUCION_MASIVA_OLT_CMTS',
            'DWH_DEVOLUCION_MASIV_DETALLE_OLT_CMTS',
            'TMP_BSCS_CF_OLT_CMTS',
            'TABLA_DWH_OLT_CMTS',
            'TMP_SPCODE_BSCS_HIST_OLT_CMTS',
            'WRK_DEV_INC_MASI_BSCS_OLT_CMTS',
            'WRK_DEVOL_INC_MASIV_OLT_CMTS',
            'TMP_INSTXPROD_MASIV_OLT_CMTS',
            'CORRECTO_SGA_BSCS_TMP_OLT_CMTS',
            'UNIQUE_CODINSSRV_TMP_OLT_CMTS',
            'TMP_DEVOLUCION_MASIVO_OLT_CMTS',
            'TMP_INSSR_PRODUCT_OLT_CMTS',
            'TMP_SERVAFEC_INSPRD_OLT_CMTS',
            'TMP_INSSRV_SRVAF_TYP_OLT_CMTS',
            'TMP_INSSRV_SERVAFEC_OLT_CMTS',
            'TMP_ABONADOS_NODOS_OLT_CMTS',
            'TMP_SRC_VALIDOS_OLT_CMTS',
            'OLT_CMTS_CODCLI',
            'OLT_MAC_FINAL',
            'CMTS_TABLE_FINAL',
        ];
        $this->dropIndexIfExists("USRAES.IDX_USRAES_TMP_SA_DEVOL_CASO1_OLT_CMTS_{$suffix}");

        foreach ($tables as $table) {
            $this->dropTableIfExists("USRAES.{$table}_{$suffix}");
        }
    }

    private function rebuildSourceValidFilterTable(int $typeId): void
    {
        $validTable = $this->tableName('TMP_SRC_VALIDOS_OLT_CMTS');
        $sourceTable = $typeId === 1
            ? $this->tableName('OLT_MAC_FINAL')
            : $this->tableName('CMTS_TABLE_FINAL');

        $this->dropTableIfExists($validTable);

        DB::connection("oracle")->statement("CREATE TABLE {$validTable} NOLOGGING AS
            SELECT DISTINCT
                   CUSTOMER_ID_CODCLI CODCLI,
                   DNI_RUC,
                   REGEXP_REPLACE(DNI_RUC, '[^0-9]+', '') DNI_RUC_LIMPIO,
                   'Telefonia Fija' FAMILIA
            FROM {$sourceTable}
            WHERE CUSTOMER_ID_CODCLI IS NOT NULL
              AND SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
              AND DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'

            UNION

            SELECT DISTINCT
                   CUSTOMER_ID_CODCLI CODCLI,
                   DNI_RUC,
                   REGEXP_REPLACE(DNI_RUC, '[^0-9]+', '') DNI_RUC_LIMPIO,
                   'Acceso Dedicado a Internet' FAMILIA
            FROM {$sourceTable}
            WHERE CUSTOMER_ID_CODCLI IS NOT NULL
              AND SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
              AND DESCRIPCION_PRODUCTO LIKE '%MBPS%'

            UNION

            SELECT DISTINCT
                   CUSTOMER_ID_CODCLI CODCLI,
                   DNI_RUC,
                   REGEXP_REPLACE(DNI_RUC, '[^0-9]+', '') DNI_RUC_LIMPIO,
                   'Cable' FAMILIA
            FROM {$sourceTable}
            WHERE CUSTOMER_ID_CODCLI IS NOT NULL
              AND SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
              AND DESCRIPCION_PRODUCTO LIKE '%TV%'");
    }

    private function rebuildCodcliTemporaryTable(): void
    {
        $codcliTable = $this->tableName('OLT_CMTS_CODCLI');
        $validTable = $this->tableName('TMP_SRC_VALIDOS_OLT_CMTS');

        $this->dropTableIfExists($codcliTable);

        DB::connection("oracle")->statement("CREATE TABLE {$codcliTable} NOLOGGING AS
            SELECT DISTINCT CODCLI
            FROM {$validTable}
            WHERE CODCLI IS NOT NULL");
    }

    private function rebuildDevolucionTemporaryTable(int $typeId, DateTime $fecha): void
    {
        $this->rebuildExtraccionTemporalWorkflow($fecha);
        $this->applyFinalFamilyFiltersToDevolucion($typeId);
    }

    private function rebuildExtraccionTemporalWorkflow(DateTime $fecha): void
    {
        $fechaIni = $fecha->format('d/m/Y') . ' 00:00:00';
        $fechaFin = $fecha->format('d/m/Y') . ' 01:00:00';
        $partition = 'P_' . $fecha->format('Ym');

        $tmpAbonados = $this->tableName('TMP_ABONADOS_NODOS_OLT_CMTS');
        $tmpInssrvServafec = $this->tableName('TMP_INSSRV_SERVAFEC_OLT_CMTS');
        $tmpInssrvSrvafTyp = $this->tableName('TMP_INSSRV_SRVAF_TYP_OLT_CMTS');
        $tmpServafecInsprd = $this->tableName('TMP_SERVAFEC_INSPRD_OLT_CMTS');
        $tmpInssrProduct = $this->tableName('TMP_INSSR_PRODUCT_OLT_CMTS');
        $tmpDevolucionMasivo = $this->tableName('TMP_DEVOLUCION_MASIVO_OLT_CMTS');
        $uniqueCodinssrv = $this->tableName('UNIQUE_CODINSSRV_TMP_OLT_CMTS');
        $correctoSgaBscs = $this->tableName('CORRECTO_SGA_BSCS_TMP_OLT_CMTS');
        $tmpInstxprodMasiv = $this->tableName('TMP_INSTXPROD_MASIV_OLT_CMTS');
        $wrkDevolIncMasiv = $this->tableName('WRK_DEVOL_INC_MASIV_OLT_CMTS');
        $wrkDevIncMasivBscs = $this->tableName('WRK_DEV_INC_MASI_BSCS_OLT_CMTS');
        $tmpSpcodeBscsHist = $this->tableName('TMP_SPCODE_BSCS_HIST_OLT_CMTS');
        $tablaDwh = $this->tableName('TABLA_DWH_OLT_CMTS');
        $tmpBscsCf = $this->tableName('TMP_BSCS_CF_OLT_CMTS');
        $dwhDetalle = $this->tableName('DWH_DEVOLUCION_MASIV_DETALLE_OLT_CMTS');
        $wrkDevolucionMasiva = $this->tableName('WRK_DEVOLUCION_MASIVA_OLT_CMTS');
        $tmpDevinstxprod = $this->tableName('TMP_DEVINSTXPROD_OLT_CMTS');
        $tmpSaDevolCaso1 = $this->tableName('TMP_SA_DEVOL_CASO1_OLT_CMTS');
        $idxTmpSaDevolCaso1 = "USRAES.IDX_USRAES_TMP_SA_DEVOL_CASO1_OLT_CMTS_{$this->userIdentifier}";
        $tmpBaseFijaDesactivos = $this->tableName('TMP_BASE_FIJA_DESACTIVOS_OLT_CMTS');
        $codcliTable = $this->tableName('OLT_CMTS_CODCLI');

        $this->executeOracleStatements([
            "CREATE TABLE {$tmpAbonados} NOLOGGING AS
                SELECT DISTINCT
                    CODCLI,
                    CAST(NULL AS VARCHAR2(10)) CODSUC,
                    CAST(NULL AS VARCHAR2(10)) IDPLANO
                FROM {$codcliTable}",

            "CREATE TABLE {$tmpInssrvServafec} NOLOGGING AS
                SELECT I.CID,
                       I.NUMERO,
                       I.ESTINSSRV,
                       I.CODINSSRV,
                       I.CODCLI,
                       I.TIPSRV,
                       I.FECINI FCHINI_INST,
                       I.FECFIN FCHFIN_INST,
                       I.DIRECCION,
                       I.DESCRIPCION SEDE,
                       I.CODUBI,
                       J.CODSUC,
                       J.IDPLANO,
                       I.CO_ID,
                       I.NUMSEC,
                       I.CUSTOMER_ID
                FROM {$tmpAbonados} J
                JOIN DWS.SA_SOLOT ST
                    ON J.CODCLI = ST.CUSTOMER_ID
                JOIN DWS.SA_INSSRV I
                    ON ST.CODCLI = I.CODCLI
                WHERE I.TIPSRV IN (
                    SELECT DISTINCT E.TIPSRV
                    FROM USRAES.SA_DEVOLUCION_EQUIVALENCIAS E
                    WHERE UPPER(E.OSIPTEL) LIKE '%TELEFONIA FIJA LOCAL%'
                       OR UPPER(E.OSIPTEL) LIKE '%INTERNET%'
                       OR UPPER(E.OSIPTEL) LIKE '%CABLE%'
                       OR UPPER(E.OSIPTEL) LIKE '%TV%'
                )",

            "CREATE TABLE {$tmpInssrvSrvafTyp} NOLOGGING AS
                SELECT I.*,
                       Y.DSCTIPSRV SERVICIO,
                       CAST('' AS VARCHAR2(100)) NOMPVC,
                       CAST('' AS VARCHAR2(100)) NOMEST,
                       CAST('' AS VARCHAR2(100)) NOMDST
                FROM {$tmpInssrvServafec} I
                LEFT JOIN DWS.SA_TYSTIPSRV Y
                    ON I.TIPSRV = Y.TIPSRV",

            "CREATE TABLE {$tmpServafecInsprd} NOLOGGING PARALLEL 8 AS
                SELECT I.CID,
                       I.NUMERO,
                       I.ESTINSSRV,
                       I.CODINSSRV,
                       I.CODCLI,
                       I.TIPSRV,
                       I.FCHINI_INST,
                       I.FCHFIN_INST,
                       I.DIRECCION,
                       I.SEDE,
                       I.CODUBI,
                       I.SERVICIO,
                       I.CODSUC,
                       I.IDPLANO,
                       I.NOMPVC,
                       I.NOMEST,
                       I.NOMDST,
                       I.CO_ID,
                       I.NUMSEC,
                       I.CUSTOMER_ID,
                       P.PID,
                       P.DESCRIPCION,
                       P.CODSRV,
                       P.FECINI,
                       P.FECFIN,
                       P.ESTINSPRD,
                       P.NUMSLC
                FROM {$tmpInssrvSrvafTyp} I
                JOIN DWS.SA_INSPRD P
                    ON I.CODINSSRV = P.CODINSSRV",

            "CREATE TABLE {$tmpInssrProduct} NOLOGGING AS
                SELECT P.*,
                       SR.DSCSRV,
                       '{$fechaIni}' FEC_INI_INCIDENCIA,
                       '{$fechaFin}' FEC_FIN_INCIDENCIA
                FROM {$tmpServafecInsprd} P
                LEFT JOIN DWS.SA_TYSTABSRV SR
                    ON P.CODSRV = SR.CODSRV
                WHERE P.FECINI < TRUNC(TO_DATE('{$fechaFin}', 'DD/MM/YYYY HH24:MI:SS'), 'DD')
                  AND (P.FECFIN > TRUNC(TO_DATE('{$fechaIni}', 'DD/MM/YYYY HH24:MI:SS'), 'DD') OR P.FECFIN IS NULL)",

            "CREATE TABLE {$tmpDevolucionMasivo} NOLOGGING PARALLEL 8 AS
                SELECT CL.CODSECMARK COD_SECTOR,
                       S.DSCSECMARK SECTOR,
                       I.CID,
                       I.NUMERO,
                       I.SERVICIO,
                       I.ESTINSSRV,
                       I.CODINSSRV,
                       I.CODCLI,
                       I.TIPSRV,
                       I.FCHINI_INST,
                       I.FCHFIN_INST,
                       I.DIRECCION,
                       I.DESCRIPCION SEDE,
                       CL.NOMCLI,
                       CL.NTDIDE,
                       TI.DESCINT TIPDOC,
                       I.CODUBI,
                       I.NOMPVC,
                       I.NOMEST,
                       I.NOMDST,
                       I.PID,
                       I.DESCRIPCION,
                       I.CODSRV,
                       I.DSCSRV,
                       I.FECINI,
                       I.FECFIN,
                       I.ESTINSPRD,
                       I.NUMSLC,
                       I.CODSUC,
                       I.IDPLANO,
                       I.FEC_INI_INCIDENCIA,
                       I.FEC_FIN_INCIDENCIA,
                       I.CO_ID,
                       I.NUMSEC,
                       I.CUSTOMER_ID
                FROM {$tmpInssrProduct} I
                JOIN DWS.SA_VTATABCLI CL
                    ON I.CODCLI = CL.CODCLI
                LEFT JOIN DWS.SA_VTATABSECMARK S
                    ON CL.CODSECMARK = S.CODSECMARK
                LEFT JOIN DWS.SA_VTATIPDID TI
                    ON CL.TIPDIDE = TI.TIPDIDE",

            "CREATE TABLE {$uniqueCodinssrv} NOLOGGING PARALLEL 8 AS
                SELECT CODINSSRV,
                       SUBSTR(FEC_INI_INCIDENCIA, 7, 4) || '-' || SUBSTR(FEC_INI_INCIDENCIA, 4, 2) || '-' || SUBSTR(FEC_INI_INCIDENCIA, 1, 2) FEC_INI_INCIDENCIA,
                       SUBSTR(FEC_FIN_INCIDENCIA, 7, 4) || '-' || SUBSTR(FEC_FIN_INCIDENCIA, 4, 2) || '-' || SUBSTR(FEC_FIN_INCIDENCIA, 1, 2) FEC_FIN_INCIDENCIA
                FROM {$tmpDevolucionMasivo}
                GROUP BY CODINSSRV, FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA",

            "CREATE TABLE {$correctoSgaBscs} NOLOGGING PARALLEL 8 AS
                SELECT *
                FROM (
                    SELECT AA.*,
                           ROW_NUMBER() OVER (PARTITION BY AA.CODINSSRV ORDER BY AA.FECUSU DESC) FLAG,
                           CASE WHEN COD_ID_BSCS IS NOT NULL THEN 'BSCS' ELSE 'SGA' END FUENTE
                    FROM (
                        SELECT AA.*,
                               BB.CODCLI CODCLI_V2,
                               BB.CUSTOMER_ID CUSTOMER_ID_BSCS,
                               BB.COD_ID COD_ID_BSCS
                        FROM (
                            SELECT AA.*,
                                   BB.CODSOLOT,
                                   BB.FECUSU
                            FROM {$uniqueCodinssrv} AA
                            LEFT JOIN DWS.SA_SOLOTPTO BB
                                ON AA.FEC_INI_INCIDENCIA >= TO_CHAR(BB.FECUSU, 'YYYY-MM-DD')
                               AND AA.CODINSSRV = BB.CODINSSRV
                        ) AA
                        LEFT JOIN DWS.SA_SOLOT BB
                            ON AA.CODSOLOT = BB.CODSOLOT
                           AND BB.ESTSOL IN (12, 29)
                           AND AA.FEC_INI_INCIDENCIA >= TO_CHAR(BB.FECUSU, 'YYYY-MM-DD')
                    ) AA
                )
                WHERE FLAG = 1",

            "MERGE INTO {$tmpDevolucionMasivo} A
                USING {$correctoSgaBscs} B
                   ON (A.CODINSSRV = B.CODINSSRV)
                WHEN MATCHED THEN
                    UPDATE SET A.CO_ID = B.COD_ID_BSCS,
                               A.CUSTOMER_ID = B.CUSTOMER_ID_BSCS",

            "CREATE TABLE {$tmpInstxprodMasiv} NOLOGGING PARALLEL 8 AS
                SELECT B.CODINSSRV,
                       B.DESCRIPCION SEDE,
                       B.DIRECCION DIREC_INSTALACION,
                       B.CODCLI,
                       B.NOMCLI,
                       B.NTDIDE NRO_DOC,
                       B.TIPDOC,
                       B.NUMERO,
                       B.CID,
                       B.TIPSRV,
                       B.SERVICIO FAMILIA,
                       B.FCHINI_INST,
                       B.FCHFIN_INST,
                       B.ESTINSSRV,
                       B.FEC_INI_INCIDENCIA,
                       B.FEC_FIN_INCIDENCIA,
                       (TO_DATE(B.FEC_FIN_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS') - TO_DATE(B.FEC_INI_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS')) * 24 * 60 MINUTOS_AFECTACION,
                       B.NOMEST DPTO,
                       B.NOMPVC PROVINCIA,
                       B.NOMDST DISTRITO,
                       B.IDPLANO,
                       B.CODSUC,
                       B.SECTOR,
                       B.NUMSLC,
                       B.PID,
                       B.CODSRV,
                       B.DSCSRV,
                       B.FECINI FECINI_INSPROD,
                       B.FECFIN FECFIN_INSPROD,
                       B.ESTINSPRD ESTADO_INSTPROD,
                       Y.CODCLI CODCLI_INST,
                       Y.IDINSTPROD,
                       Y.DESCRIPCION DESCRIPCION_INST,
                       Y.IDPRODUCTO,
                       ROUND(Y.MONTOCR, 2) MONTOCR_PROD,
                       DECODE(Y.IDMONEDACR, 1, 'SOLES', 2, 'DOLARES') MONEDA,
                       Y.CICFAC,
                       Y.ESTADO,
                       Y.FECINI,
                       Y.FECFIN
                FROM DWS.SA_INSTXPRODUCTO Y
                JOIN {$tmpDevolucionMasivo} B
                    ON Y.CODCLI = B.CODCLI
                   AND Y.PID = B.PID
                WHERE B.CO_ID IS NULL
                  AND Y.MONTOCR > 0",

            "CREATE TABLE {$wrkDevolIncMasiv} NOLOGGING PARALLEL 8 AS
                SELECT Y.*,
                       NVL(Y.MONTOCR_PROD, 0) CR_NETO,
                       PR.DESCRIPCION DESC_PRODUCTO
                FROM {$tmpInstxprodMasiv} Y
                JOIN DWS.SA_PRODUCTO PR
                    ON Y.IDPRODUCTO = PR.IDPRODUCTO
                WHERE PR.IDGRUPOPRODUCTO IS NULL
                  AND PR.NIVEL = 2
                  AND PR.IDMONEDACR IS NOT NULL
                  AND PR.FLGDEVINTP = 1",

            "CREATE TABLE {$wrkDevIncMasivBscs} NOLOGGING PARALLEL 4 AS
                SELECT *
                FROM (
                    SELECT T.CODINSSRV,
                           T.DESCRIPCION,
                           F.CUSTOMER_ACCOUNT_BILLING_ADDRESS,
                           T.CODCLI,
                           F.CUSTOMER_ACCOUNT_SC CUSTOMER_ID,
                           F.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO,
                           F.CUSTOMER_FULL_NAME RAZON_SOCIAL,
                           F.ID_CARD_VALUE NRO_DOCUMENTO,
                           F.ID_CARD_TYPE_VALUE TIPDOC_CLIENTE,
                           T.NUMERO,
                           T.CID,
                           T.TIPSRV,
                           T.SERVICIO FAMILIA,
                           F.AGREEMENT_START_DATE FECHAHORAALTA,
                           F.AGREEMENT_END_DATE FECHABAJA,
                           F.AGREEMENT_STATUS ESTADO_CONTRATO,
                           T.FEC_INI_INCIDENCIA,
                           T.FEC_FIN_INCIDENCIA,
                           (SUBSTR(T.FEC_INI_INCIDENCIA, 7, 4) || SUBSTR(T.FEC_INI_INCIDENCIA, 4, 2) || SUBSTR(T.FEC_INI_INCIDENCIA, 1, 2)) FECHA,
                           (TO_DATE(T.FEC_FIN_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS') - TO_DATE(T.FEC_INI_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS')) * 24 * 60 MINUTOS_AFECTACION,
                           T.NOMEST DPTO,
                           T.NOMPVC PROVINCIA,
                           T.NOMDST DISTRITO,
                           T.IDPLANO,
                           T.CODSUC,
                           T.SECTOR,
                           T.NUMSLC,
                           T.FCHINI_INST,
                           T.FCHFIN_INST,
                           F.CUSTOMER_ACCOUNT_DESC CUSTCODE,
                           F.AGREEMENT_CONTRACT_NUMBER CO_ID,
                           F.CUSTOMER_FIRST_NAME || ' ' || F.CUSTOMER_LAST_NAME CONTACTO,
                           ROW_NUMBER() OVER (PARTITION BY T.NUMERO ORDER BY F.AGREEMENT_START_DATE DESC) FLAG_1
                    FROM DWA.DW_M_SUBSCRIPTION F
                    JOIN (SELECT * FROM {$tmpDevolucionMasivo} WHERE CO_ID IS NOT NULL) T
                        ON TO_CHAR(F.AGREEMENT_CONTRACT_NUMBER) = TO_CHAR(T.CO_ID)
                )
                WHERE FLAG_1 = 1",

            "ALTER TABLE {$wrkDevIncMasivBscs} ADD (CARGO_FIJO NUMBER)",
            "ALTER TABLE {$wrkDevIncMasivBscs} ADD FLGCONSIDERA VARCHAR2(5)",

            "CREATE TABLE {$tmpSpcodeBscsHist} NOLOGGING AS
                SELECT SPH.CO_ID,
                       SPH.SNCODE,
                       SPH.SPCODE,
                       TE.FECHA
                FROM DWS.SA_PR_SERV_SPCODE_HIST SPH
                JOIN {$wrkDevIncMasivBscs} TE
                    ON SPH.CO_ID = TE.CO_ID
                WHERE SPH.SNCODE IN (
                    SELECT SSH.SNCODE
                    FROM DWS.SA_PR_SERV_STATUS_HIST SSH
                    WHERE SSH.STATUS = 'A'
                      AND SSH.CO_ID = TE.CO_ID
                      AND SSH.HISTNO = (
                          SELECT MAX(H.HISTNO)
                          FROM DWS.SA_PR_SERV_STATUS_HIST H
                          WHERE H.CO_ID = SSH.CO_ID
                            AND H.SNCODE = SSH.SNCODE
                            AND H.VALID_FROM_DATE <= TO_DATE(TE.FECHA, 'YYYYMMDD') + 1
                      )
                      AND EXISTS (
                          SELECT 1
                          FROM DWS.SA_CONTRACT_ALL CA
                          WHERE CA.CO_ID = SSH.CO_ID
                            AND CA.SCCODE = 6
                      )
                )
                  AND SPH.HISTNO = (
                      SELECT MAX(SP.HISTNO)
                      FROM DWS.SA_PR_SERV_SPCODE_HIST SP
                      WHERE SP.CO_ID = SPH.CO_ID
                        AND SP.SNCODE = SPH.SNCODE
                        AND SP.VALID_FROM_DATE <= TO_DATE(TE.FECHA, 'YYYYMMDD') + 1
                  )",

            "CREATE TABLE {$tablaDwh} NOLOGGING AS
                SELECT PS.CO_ID,
                       PS.SNCODE,
                       T.SPCODE,
                       PS.ACCESSFEE,
                       PS.OVW_ACC_PRD,
                       PS.OVW_ACCESS,
                       (
                           SELECT RH.TMCODE
                           FROM DWS.SA_RATEPLAN_HIST RH
                           WHERE RH.CO_ID = PS.CO_ID
                             AND RH.SEQNO = (
                                 SELECT MAX(HJ.SEQNO)
                                 FROM DWS.SA_RATEPLAN_HIST HJ
                                 WHERE HJ.CO_ID = RH.CO_ID
                                   AND HJ.TMCODE_DATE <= TO_DATE(T.FECHA, 'YYYYMMDD') + 1
                             )
                       ) TMCODE,
                       T.FECHA
                FROM DWS.SA_PROFILE_SERVICE PS
                JOIN {$tmpSpcodeBscsHist} T
                    ON PS.CO_ID = T.CO_ID
                   AND PS.SNCODE = T.SNCODE",

            "CREATE TABLE {$tmpBscsCf} NOLOGGING AS
                SELECT CO_ID,
                       SNCODE,
                       SPCODE,
                       ACCESSFEE,
                       OVW_ACC_PRD,
                       OVW_ACCESS,
                       TMCODE,
                       FECHA,
                       COSTO
                FROM (
                    SELECT DH.*,
                           DECODE(DH.OVW_ACC_PRD, 0, TMB.ACCESSFEE, -2, TMB.ACCESSFEE,
                               DECODE(DH.OVW_ACCESS, NULL, TMB.ACCESSFEE, 'R', NVL((TMB.ACCESSFEE * DH.ACCESSFEE), 0), NVL(DH.ACCESSFEE, 0))
                           ) COSTO
                    FROM {$tablaDwh} DH,
                         DWS.SA_MPULKTMB TMB
                    WHERE DH.TMCODE = TMB.TMCODE
                      AND DH.SNCODE = TMB.SNCODE
                      AND DH.SPCODE = TMB.SPCODE
                      AND TMB.VSCODE = (
                          SELECT MAX(V.VSCODE)
                          FROM DWS.SA_RATEPLAN_VERSION V
                          WHERE V.TMCODE = TMB.TMCODE
                            AND V.STATUS = 'P'
                            AND V.VSDATE <= TO_DATE(DH.FECHA, 'YYYYMMDD') + 1
                      )
                )
                GROUP BY CO_ID, SNCODE, SPCODE, ACCESSFEE, OVW_ACC_PRD, OVW_ACCESS, TMCODE, FECHA, COSTO",
        ]);

        $this->createDwhDevolucionDetalleTable($dwhDetalle);

        $this->executeOracleStatements([
            "MERGE INTO {$wrkDevIncMasivBscs} A
                USING (
                    SELECT C.CO_ID,
                           SUM(C.COSTO) COSTO
                    FROM {$tmpBscsCf} C,
                         DWS.SA_MPUSPTAB M
                    WHERE C.SPCODE = M.SPCODE
                      AND C.COSTO > 0
                    GROUP BY C.CO_ID
                ) B
                   ON (A.CO_ID = B.CO_ID)
                WHEN MATCHED THEN
                    UPDATE SET A.CARGO_FIJO = B.COSTO",

            "INSERT INTO {$dwhDetalle} (CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA,
                    IDPLANO, FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION, DPTO, PROVINCIA, DISTRITO,
                    MONEDA, CR_NETO, FCHINI_INST, FCHFIN_INST, CICFAC_DEVOL, CUSTCODE, CO_ID, FECHAALTA,
                    ESTADO_CONTRATO, CUSTOMER_ID, FUENTE)
                SELECT T.CODCLI,
                       T.RAZON_SOCIAL,
                       T.NRO_DOCUMENTO,
                       T.TIPDOC_CLIENTE,
                       T.NUMERO,
                       T.CID,
                       T.FAMILIA,
                       T.IDPLANO,
                       TO_DATE(T.FEC_INI_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS'),
                       TO_DATE(T.FEC_FIN_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS'),
                       T.MINUTOS_AFECTACION,
                       T.DPTO,
                       T.PROVINCIA,
                       T.DISTRITO,
                       'SOLES',
                       T.CARGO_FIJO,
                       T.FCHINI_INST,
                       T.FCHFIN_INST,
                       T.CICLO,
                       T.CUSTCODE,
                       T.CO_ID,
                       T.FECHAHORAALTA,
                       T.ESTADO_CONTRATO,
                       T.CUSTOMER_ID,
                       'BSCS'
                FROM {$wrkDevIncMasivBscs} T
                WHERE T.FLGCONSIDERA IS NULL
                  AND T.CARGO_FIJO > 0
                  AND T.CARGO_FIJO IS NOT NULL",

            "INSERT INTO {$dwhDetalle} (CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA, CODSRV, DSCSRV,
                    IDPLANO, FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION, DPTO, PROVINCIA, DISTRITO,
                    MONEDA, CR_NETO, FCHINI_INST, FCHFIN_INST, FUENTE)
                SELECT T.CODCLI,
                       T.NOMCLI,
                       T.NRO_DOC,
                       T.TIPDOC,
                       T.NUMERO,
                       T.CID,
                       T.FAMILIA,
                       T.CODSRV,
                       T.DSCSRV,
                       T.IDPLANO,
                       TO_DATE(T.FEC_INI_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS'),
                       TO_DATE(T.FEC_FIN_INCIDENCIA, 'DD/MM/YYYY HH24:MI:SS'),
                       T.MINUTOS_AFECTACION,
                       T.DPTO,
                       T.PROVINCIA,
                       T.DISTRITO,
                       T.MONEDA,
                       T.CR_NETO,
                       T.FECINI_INSPROD,
                       T.FECFIN_INSPROD,
                       'SGA'
                FROM {$wrkDevolIncMasiv} T
                WHERE T.CR_NETO > 0
                  AND T.CR_NETO IS NOT NULL",

            "CREATE TABLE {$wrkDevolucionMasiva} NOLOGGING PARALLEL 8 AS
                SELECT W.CODCLI,
                       W.NOMCLI,
                       W.TIPDOC,
                       W.NRO_DOC,
                       W.CID,
                       W.NUMERO,
                       I.TIPSRV,
                       W.FAMILIA,
                       W.DSCSRV,
                       W.CODSRV,
                       W.IDPLANO,
                       W.FEC_INI_INCIDENCIA,
                       W.FEC_FIN_INCIDENCIA,
                       W.MINUTOS_AFECTACION,
                       W.DPTO,
                       W.PROVINCIA,
                       W.FCHINI_INST,
                       W.FCHFIN_INST,
                       W.DISTRITO,
                       W.MONEDA,
                       W.CR_NETO
                FROM {$dwhDetalle} W,
                     (SELECT TIPSRV, DSCTIPSRV FROM USRAES.SA_DEVOLUCION_EQUIVALENCIAS) I
                WHERE TRIM(W.FAMILIA) = TRIM(I.DSCTIPSRV)",

            "CREATE TABLE {$tmpDevinstxprod} NOLOGGING PARALLEL 4 AS
                SELECT S.CODCLI,
                       S.ESTINSSRV,
                       S.CODINSSRV,
                       I.MONTOCR,
                       I.IDMONEDACR,
                       I.IDINSTPROD,
                       I.ESTADO,
                       I.FECINI,
                       I.FECFIN,
                       I.CICFAC,
                       I.DESCRIPCION DSC_PRODUCTO,
                       I.IDPRODUCTO,
                       I.PID
                FROM DWS.SA_INSSRV S
                JOIN {$wrkDevolucionMasiva} W
                    ON S.CODCLI = W.CODCLI
                JOIN DWS.SA_INSTXPRODUCTO I
                    ON W.CODCLI = I.CODCLI
                WHERE S.ESTINSSRV = 1
                  AND I.MONTOCR > 0
                  AND I.ESTADO = 1
                  AND I.FECFIN IS NULL
                  AND I.CICFAC IN (11, 21, 26, 36, 37, 3, 29, 6, 12, 20, 23)
                  AND I.IDPRODUCTO NOT IN (771, 502, 702, 725, 689, 4, 745, 721, 501)
                  AND I.IDMONEDACR = 1",

            "CREATE TABLE {$tmpSaDevolCaso1} NOLOGGING PARALLEL 4 AS
                SELECT I.CODCLI,
                       P.PID,
                       P.DESCRIPCION,
                       I.ESTINSSRV,
                       P.ESTINSPRD,
                       P.CODINSSRV,
                       P.FECINI FECINI_INSPRD,
                       P.FECFIN FECFIN_INSPRD,
                       P.NUMSLC,
                       TY.DSCSRV,
                       P.CODSRV,
                       I.MONTOCR,
                       I.IDMONEDACR,
                       I.IDINSTPROD,
                       I.ESTADO,
                       I.FECINI,
                       I.FECFIN,
                       I.CICFAC,
                       I.DSC_PRODUCTO,
                       I.IDPRODUCTO
                FROM {$tmpDevinstxprod} I
                JOIN DWS.SA_INSPRD P
                    ON I.CODINSSRV = P.CODINSSRV
                   AND I.PID = P.PID
                LEFT JOIN DWS.SA_TYSTABSRV TY
                    ON P.CODSRV = TY.CODSRV
                WHERE TY.DSCSRV NOT LIKE '%Alquiler%'
                  AND P.ESTINSPRD = 1",

            "ALTER TABLE {$tmpSaDevolCaso1} ADD (CR_NETO NUMBER)",
            "CREATE INDEX {$idxTmpSaDevolCaso1} ON {$tmpSaDevolCaso1}(CODCLI, IDINSTPROD)",

            "ALTER TABLE {$wrkDevolucionMasiva} ADD (
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
                    FCH_CALC_TASA DATE,
                    INTERES NUMBER,
                    OBS VARCHAR2(30),
                    SERVICIO_DEVOL VARCHAR2(100),
                    TASA NUMBER
                )",

            "ALTER TABLE {$wrkDevolucionMasiva} ADD (
                    FAC_ULTI_SBS NUMBER,
                    FAC_NUEV_REFE NUMBER,
                    FAC_ACUM_PROY NUMBER,
                    FAC_ACUM_INI NUMBER
                )",

            "UPDATE {$wrkDevolucionMasiva}
                SET ULT_FECHA = TO_NUMBER(TO_CHAR(LAST_DAY(TRUNC(FEC_INI_INCIDENCIA, 'DD')), 'DD'))",
        ]);

        $this->executeOracleBlock("DECLARE
                V_TIPSRV VARCHAR2(5);
            BEGIN
                SELECT MAX(I.TIPSRV) INTO V_TIPSRV FROM {$wrkDevolucionMasiva} I;

                IF (V_TIPSRV = '0006' OR V_TIPSRV = '0054') THEN
                    UPDATE {$wrkDevolucionMasiva} W
                       SET BANWID = (
                           SELECT MAX(TY.BANWID)
                           FROM DWS.SA_TYSTABSRV TY
                           WHERE TY.CODSRV = W.CODSRV
                             AND TY.TIPSRV = W.TIPSRV
                             AND TY.TIPSRV = '0006'
                       )
                    WHERE EXISTS (
                        SELECT 1
                        FROM DWS.SA_TYSTABSRV TY
                        WHERE TY.CODSRV = W.CODSRV
                          AND TY.TIPSRV = W.TIPSRV
                          AND TY.TIPSRV = '0006'
                    );
                END IF;
            END;");

        $this->executeOracleBlock("DECLARE
                CURSOR C_DEVOL IS
                    SELECT DISTINCT A.*
                    FROM {$tmpSaDevolCaso1} A
                    WHERE NVL(A.CR_NETO, 0) > 0
                      AND A.CODSRV NOT IN ('4602','AALC','AERW','AAQD','AAQE','AALE','AAQF','AAQK');
            BEGIN
                FOR R_DEVOL IN C_DEVOL LOOP
                    UPDATE {$wrkDevolucionMasiva} W
                       SET W.IDINTPROD_DEVOL = R_DEVOL.IDINSTPROD,
                           W.CICFAC_DEVOL = R_DEVOL.CICFAC,
                           W.FECINI_DEVOL = R_DEVOL.FECINI,
                           W.FECFIN_DEVOL = R_DEVOL.FECFIN,
                           W.ESTADO_IDINTPROD_DEVOL = R_DEVOL.ESTADO,
                           W.OBS = 'DEVOLVER_FACTURA',
                           W.SERVICIO_DEVOL = R_DEVOL.DSC_PRODUCTO
                     WHERE W.CODCLI = R_DEVOL.CODCLI;
                END LOOP;
            END;");

        $this->executeOracleStatements([
            "DELETE FROM {$dwhDetalle}
                WHERE FUENTE = 'SGA'
                  AND TO_NUMBER(CICFAC_DEVOL) IN (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)",

            "ALTER TABLE {$dwhDetalle} ADD MODO_CONTRATACION VARCHAR2(100)",
            "ALTER TABLE {$dwhDetalle} ADD TIPO_CLIENTE VARCHAR2(100)",
            "ALTER TABLE {$dwhDetalle} ADD MSISDN_DEVOLVER VARCHAR2(100)",
            "ALTER TABLE {$dwhDetalle} ADD COMENTARIOS VARCHAR2(100)",
            "ALTER TABLE {$dwhDetalle} ADD FECHA_BAJA DATE",
        ]);

        $this->createTmpBaseFijaDesactivosTable($tmpBaseFijaDesactivos);

        $this->executeOracleStatements([
            "INSERT INTO {$tmpBaseFijaDesactivos}(
                    CODCLI, NOMCLI, NRO_DOC, TIPDOC, NUMERO, CID, FAMILIA, IDPLANO, FEC_INI_INCIDENCIA, FEC_FIN_INCIDENCIA, MINUTOS_AFECTACION,
                    DPTO, PROVINCIA, DISTRITO, MONEDA, CR_NETO, FCHINI_INST, FCHFIN_INST, CICFAC_DEVOL, CUSTCODE, CO_ID, FECHAALTA, ESTADO_CONTRATO, CUSTOMER_ID,
                    FUENTE, CODSRV, DSCSRV, OBS, ESTADO_IDINTPROD_DEVOL, SERVICIO_DEVOL, IDINTPROD_DEVOL, TASA_AL, FCH_CALC_TASA, TASA, MONEDA_DEVOL, MONTO_DEVOL_CIGV,
                    INTERES, MONTO_PRINC_CIGV, ULT_FECHA, CR_NETOCIGV, MONTO_PRINCIPAL,
                    FECHA_BAJA, MODO_CONTRATACION, TIPO_CLIENTE, MSISDN_DEVOLVER, COMENTARIOS
                )
                SELECT CODCLI,
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
                       CASE
                           WHEN MOVIL_STATUS IN ('A', 'G') THEN NULL
                           WHEN (MOVIL_STATUS NOT IN ('A', 'G') AND AGREEMENT_END_DATE < FCHFIN_INST)
                                OR TRUNC(AGREEMENT_END_DATE) = TO_DATE('31/12/9999', 'DD/MM/YYYY')
                                OR AGREEMENT_END_DATE IS NULL THEN FCHFIN_INST
                           ELSE AGREEMENT_END_DATE
                       END FECHA_BAJA,
                       AGREEMENT_MODE MODO_CONTRATACION,
                       TIPO_CLIENTE,
                       LINEA_ACTUAL MSISDN_DEVOLVER,
                       CASE
                           WHEN MOVIL_STATUS NOT IN ('A', 'G') OR AGREEMENT_END_DATE IS NULL THEN 'DEVOLUCION WEB'
                           WHEN AGREEMENT_MODE = 'POSTPAGO' THEN 'DEVOLUCION APLICADA-POSTPAGO'
                           WHEN AGREEMENT_MODE = 'PREPAGO' THEN 'DEVOLUCION APLICADA-PREPAGO'
                       END COMENTARIOS
                FROM (
                    SELECT TK.CODCLI,
                           TK.NOMCLI,
                           TK.NRO_DOC,
                           TK.TIPDOC,
                           TK.NUMERO,
                           TK.CID,
                           TK.FAMILIA,
                           TK.IDPLANO,
                           TK.FEC_INI_INCIDENCIA,
                           TK.FEC_FIN_INCIDENCIA,
                           TK.MINUTOS_AFECTACION,
                           TK.DPTO,
                           TK.PROVINCIA,
                           TK.DISTRITO,
                           TK.MONEDA,
                           TK.CR_NETO,
                           TK.FCHINI_INST,
                           TK.FCHFIN_INST,
                           TK.CICFAC_DEVOL,
                           TK.CUSTCODE,
                           TK.CO_ID,
                           TK.FECHAALTA,
                           TK.ESTADO_CONTRATO,
                           TK.CUSTOMER_ID,
                           TK.FUENTE,
                           TK.CODSRV,
                           TK.DSCSRV,
                           TK.OBS,
                           TK.ESTADO_IDINTPROD_DEVOL,
                           TK.SERVICIO_DEVOL,
                           TK.IDINTPROD_DEVOL,
                           TK.TASA_AL,
                           TK.FCH_CALC_TASA,
                           TK.TASA,
                           TK.MONEDA_DEVOL,
                           TK.MONTO_DEVOL_CIGV,
                           TK.INTERES,
                           TK.MONTO_PRINC_CIGV,
                           TK.ULT_FECHA,
                           TK.CR_NETOCIGV,
                           TK.MONTO_PRINCIPAL,
                           TK.FECHA_BAJA,
                           TK.MODO_CONTRATACION,
                           TK.MSISDN_DEVOLVER,
                           TK.COMENTARIOS,
                           SBS.CUSTOMER_ACCOUNT_SC CLIENTE_ID,
                           CASE WHEN SBS.ID_CARD_TYPE_VALUE = 'RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_FIRST_NAME END NOMBRES,
                           CASE WHEN SBS.ID_CARD_TYPE_VALUE = 'RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_LAST_NAME END APELLIDOS,
                           SBS.CUSTOMER_FULL_NAME,
                           SBS.ID_CARD_VALUE,
                           SBS.ID_CARD_TYPE_VALUE,
                           UPPER(DECODE(SBS.AGREEMENT_MODE, 'PREPAGO', 'CONSUMER', SBS.CUSTOMER_ACCOUNT_CATEGORY_DESC)) TIPO_CLIENTE,
                           SBS.SUBSCRIPTION_SOURCE_SYSTEM_DESC,
                           SBS.AGREEMENT_PRODUCT_OFFERING_SC,
                           SBS.AGREEMENT_PRODUCT_OFFERING_DESC,
                           CASE WHEN SBS.AGREEMENT_STATUS IS NULL THEN 'D' ELSE SBS.AGREEMENT_STATUS END MOVIL_STATUS,
                           DECODE(SBS.AGREEMENT_MODE, 'POSTPAGO', SBS.CUSTOMER_ACCOUNT_SC) MOVIL_CUSTOMER_ID,
                           DECODE(SBS.AGREEMENT_MODE, 'POSTPAGO', SBS.CUSTOMER_ACCOUNT_DESC) MOVIL_CUSTCODE,
                           DECODE(SBS.AGREEMENT_MODE, 'POSTPAGO', SBS.AGREEMENT_SOURCE_CODE) MOVIL_CO_ID,
                           CASE WHEN SBS.AGREEMENT_MODE IS NULL THEN 'POSTPAGO' ELSE SBS.AGREEMENT_MODE END AGREEMENT_MODE,
                           SBS.AGREEMENT_START_DATE,
                           SBS.AGREEMENT_END_DATE,
                           SBS.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO,
                           SBS.SUBSCRIPTION_ACCESS_NUMBER LINEA_ACTUAL,
                           ROW_NUMBER() OVER (PARTITION BY TK.NRO_DOC ORDER BY CASE WHEN SBS.AGREEMENT_STATUS = 'A' THEN 1 ELSE 0 END DESC, SBS.CUSTOMER_ACCOUNT_ID DESC) R
                    FROM (
                        SELECT *
                        FROM {$dwhDetalle}
                        WHERE ((CASE FUENTE
                            WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
                            WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
                            ELSE 0 END) = 0
                            OR TO_NUMBER(CICFAC_DEVOL) IN (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)
                            OR ESTADO_CONTRATO NOT IN ('A', 'G'))
                    ) TK
                    LEFT JOIN DWA.DW_M_SUBSCRIPTION_HIST PARTITION({$partition}) SBS
                        ON SBS.ID_CARD_VALUE = TK.NRO_DOC
                       AND SBS.AGREEMENT_SERVICE_GROUP = 'MOVIL'
                    LEFT JOIN DWA.DW_T_SUBSCRIPTION_NUMBER SN
                        ON TK.FEC_INI_INCIDENCIA BETWEEN SN.START_DATE AND SN.END_DATE
                       AND SN.AGREEMENT_ID = SBS.AGREEMENT_ID
                )
                WHERE R = 1",

            "UPDATE {$dwhDetalle}
                SET COMENTARIOS = 'DEVOLUCION APLICADA-POSTPAGO',
                    MODO_CONTRATACION = 'POSTPAGO'",

            "UPDATE {$dwhDetalle}
                SET COMENTARIOS = 'DEVOLUCION WEB'
                WHERE ((CASE FUENTE
                    WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
                    WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
                    ELSE 0 END) = 0
                    OR TO_NUMBER(CICFAC_DEVOL) IN (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)
                    OR ESTADO_CONTRATO NOT IN ('A', 'G'))",

            "MERGE INTO {$dwhDetalle} DMD
                USING {$tmpBaseFijaDesactivos} TMP
                   ON (DMD.NRO_DOC = TMP.NRO_DOC)
                WHEN MATCHED THEN
                    UPDATE SET DMD.CICFAC_DEVOL = TMP.CICFAC_DEVOL,
                               DMD.CUSTCODE = TMP.CUSTCODE,
                               DMD.CO_ID = TMP.CO_ID,
                               DMD.ESTADO_CONTRATO = TMP.ESTADO_CONTRATO,
                               DMD.CUSTOMER_ID = TMP.CUSTOMER_ID,
                               DMD.FUENTE = TMP.FUENTE,
                               DMD.MODO_CONTRATACION = TMP.MODO_CONTRATACION,
                               DMD.TIPO_CLIENTE = TMP.TIPO_CLIENTE,
                               DMD.MSISDN_DEVOLVER = TMP.MSISDN_DEVOLVER,
                               DMD.COMENTARIOS = TMP.COMENTARIOS,
                               DMD.FECHA_BAJA = TMP.FECHA_BAJA
                WHERE ((CASE DMD.FUENTE
                    WHEN 'BSCS' THEN (CASE WHEN DMD.ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
                    WHEN 'SGA' THEN (CASE WHEN DMD.CICFAC_DEVOL IS NOT NULL AND DMD.FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
                    ELSE 0 END) = 0
                    OR TO_NUMBER(DMD.CICFAC_DEVOL) IN (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20))",
        ]);
    }

    private function createDwhDevolucionDetalleTable(string $tableName): void
    {
        DB::connection("oracle")->statement("CREATE TABLE {$tableName}(
            CODCLI VARCHAR2(500),
            NOMCLI VARCHAR2(500),
            NRO_DOC VARCHAR2(500),
            TIPDOC VARCHAR2(500),
            NUMERO VARCHAR2(100),
            CID NUMBER,
            FAMILIA VARCHAR2(100),
            IDPLANO VARCHAR2(100),
            FEC_INI_INCIDENCIA DATE,
            FEC_FIN_INCIDENCIA DATE,
            MINUTOS_AFECTACION NUMBER,
            DPTO VARCHAR2(100),
            PROVINCIA VARCHAR2(100),
            DISTRITO VARCHAR2(100),
            MONEDA VARCHAR2(100),
            CR_NETO NUMBER,
            FCHINI_INST DATE,
            FCHFIN_INST DATE,
            CICFAC_DEVOL VARCHAR2(100),
            CUSTCODE VARCHAR2(500),
            CO_ID VARCHAR2(500),
            FECHAALTA DATE,
            ESTADO_CONTRATO VARCHAR2(250),
            CUSTOMER_ID VARCHAR2(250),
            FUENTE VARCHAR2(100),
            CODSRV VARCHAR2(50),
            DSCSRV VARCHAR2(50),
            OBS VARCHAR2(100),
            ESTADO_IDINTPROD_DEVOL VARCHAR2(100),
            SERVICIO_DEVOL VARCHAR2(100),
            IDINTPROD_DEVOL NUMBER,
            TASA_AL DATE,
            FCH_CALC_TASA DATE,
            TASA NUMBER,
            MONEDA_DEVOL VARCHAR2(100),
            MONTO_DEVOL_CIGV NUMBER,
            INTERES NUMBER,
            MONTO_PRINC_CIGV NUMBER,
            ULT_FECHA NUMBER,
            CR_NETOCIGV NUMBER,
            MONTO_PRINCIPAL NUMBER
        )");
    }

    private function createTmpBaseFijaDesactivosTable(string $tableName): void
    {
        DB::connection("oracle")->statement("CREATE TABLE {$tableName}(
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
            FECHA_BAJA DATE,
            MODO_CONTRATACION VARCHAR2(20),
            TIPO_CLIENTE VARCHAR2(500),
            MSISDN_DEVOLVER VARCHAR2(20),
            COMENTARIOS VARCHAR2(28)
        )");
    }

    private function applyFinalFamilyFiltersToDevolucion(int $typeId): void
    {
        $dwhDetalle = $this->tableName('DWH_DEVOLUCION_MASIV_DETALLE_OLT_CMTS');
        $sourceTable = $typeId === 1
            ? $this->tableName('OLT_MAC_FINAL')
            : $this->tableName('CMTS_TABLE_FINAL');

        $this->executeOracleStatements([
            "DELETE FROM {$dwhDetalle}
                WHERE FAMILIA = 'Telefonia Fija'
                  AND TO_NUMBER(NULLIF(REGEXP_REPLACE(NRO_DOC, '[^0-9]+', ''), '')) NOT IN (
                      SELECT TO_NUMBER(NULLIF(REGEXP_REPLACE(DNI_RUC, '[^0-9]+', ''), ''))
                      FROM {$sourceTable}
                      WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
                        AND DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'
                  )",

            "DELETE FROM {$dwhDetalle}
                WHERE FAMILIA = 'Acceso Dedicado a Internet'
                  AND NRO_DOC NOT IN (
                      SELECT DNI_RUC
                      FROM {$sourceTable}
                      WHERE SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
                        AND DESCRIPCION_PRODUCTO LIKE '%MBPS%'
                  )",

            "DELETE FROM {$dwhDetalle}
                WHERE TRIM(FAMILIA) = 'Cable'
                  AND NRO_DOC NOT IN (
                      SELECT DNI_RUC
                      FROM {$sourceTable}
                      WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
                        AND DESCRIPCION_PRODUCTO LIKE '%TV%'
                  )",
        ]);
    }

    private function executeOracleStatements(array $statements): void
    {
        foreach ($statements as $statement) {
            DB::connection("oracle")->statement($statement);
        }
    }

    private function executeOracleBlock(string $block): void
    {
        DB::connection("oracle")->statement($block);
    }

    private function getOltFilteredFinalReport()
    {
        return DB::connection("oracle")->select(DB::raw($this->buildFilteredFinalQuery(false)));
    }

    private function getCmtsFilteredFinalReport()
    {
        return DB::connection("oracle")->select(DB::raw($this->buildFilteredFinalQuery(true)));
    }

    private function buildFilteredFinalQuery(bool $isCmts): string
    {
        $sourceTable = $isCmts
            ? $this->tableName('CMTS_TABLE_FINAL')
            : $this->tableName('OLT_MAC_FINAL');
        $devolucionTable = $this->tableName('DWH_DEVOLUCION_MASIV_DETALLE_OLT_CMTS');
        $cmtsFechaCondition = $isCmts
            ? "AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD') <= Z.FEC_INI_INCIDENCIA"
            : "";

        return "SELECT CUSTOMER_ID_CODCLI
            ,FUENTE
            ,SERIALNUMBER
            ,NOMBRE
            ,ID_CARD_TYPE_VALUE
            ,TELEFONO_CLARO
            ,NUMERO_ADICIONAL
            ,SERVICIO_PRODUCTO
            ,CORREO
            ,DISTRITO
            ,PROVINCIA
            ,DEPARTAMENTO
            ,DESCRIPCION_PRODUCTO
            ,AGREEMENT_STATUS
            ,AGREEMENT_STATUS_DATE
            ,AGREEMENT_START_DATE
            ,AGREEMENT_END_DATE
            ,INSTALLATION_MAP
            ,NUMERO
            ,DNI_RUC
        FROM
        (
            SELECT DISTINCT
                X.CUSTOMER_ID_CODCLI,
                X.FUENTE,
                X.SERIALNUMBER,
                X.NOMBRE,
                X.ID_CARD_TYPE_VALUE,
                X.TELEFONO_CLARO,
                X.NUMERO_ADICIONAL,
                X.SERVICIO_PRODUCTO,
                X.CORREO,
                X.DISTRITO,
                X.PROVINCIA,
                X.DEPARTAMENTO,
                X.DESCRIPCION_PRODUCTO,
                X.AGREEMENT_STATUS,
                X.AGREEMENT_STATUS_DATE,
                X.AGREEMENT_START_DATE,
                X.AGREEMENT_END_DATE,
                X.INSTALLATION_MAP,
                X.NUMERO,
                CASE
                    WHEN LENGTH(X.DNI_RUC) <= 8 AND REGEXP_REPLACE(X.DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(X.DNI_RUC, 12, '0')
                    WHEN LENGTH(X.DNI_RUC) < 8 THEN LPAD(X.DNI_RUC, 8, '0')
                    WHEN 8 < LENGTH(X.DNI_RUC) AND LENGTH(X.DNI_RUC) < 11 THEN LPAD(X.DNI_RUC, 12, '0')
                    ELSE X.DNI_RUC
                END DNI_RUC,
                ROW_NUMBER() OVER (PARTITION BY X.DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM {$sourceTable} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
                ON X.CUSTOMER_ID_CODCLI = Y.CUSTOMER_ID
            JOIN (
                SELECT * FROM {$devolucionTable}
                WHERE FAMILIA = 'Telefonia Fija'
            ) Z
                ON Y.CODCLI = Z.CODCLI
                AND TO_NUMBER(NULLIF(REGEXP_REPLACE(X.DNI_RUC, '[^0-9]+', ''), '')) = TO_NUMBER(NULLIF(REGEXP_REPLACE(Z.NRO_DOC, '[^0-9]+', ''), ''))
            WHERE X.SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
            {$cmtsFechaCondition}
            AND X.DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'

            UNION ALL

            SELECT DISTINCT
                X.CUSTOMER_ID_CODCLI,
                X.FUENTE,
                X.SERIALNUMBER,
                X.NOMBRE,
                X.ID_CARD_TYPE_VALUE,
                X.TELEFONO_CLARO,
                X.NUMERO_ADICIONAL,
                X.SERVICIO_PRODUCTO,
                X.CORREO,
                X.DISTRITO,
                X.PROVINCIA,
                X.DEPARTAMENTO,
                X.DESCRIPCION_PRODUCTO,
                X.AGREEMENT_STATUS,
                X.AGREEMENT_STATUS_DATE,
                X.AGREEMENT_START_DATE,
                X.AGREEMENT_END_DATE,
                X.INSTALLATION_MAP,
                X.NUMERO,
                CASE
                    WHEN LENGTH(X.DNI_RUC) <= 8 AND REGEXP_REPLACE(X.DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(X.DNI_RUC, 12, '0')
                    WHEN LENGTH(X.DNI_RUC) < 8 THEN LPAD(X.DNI_RUC, 8, '0')
                    WHEN 8 < LENGTH(X.DNI_RUC) AND LENGTH(X.DNI_RUC) < 11 THEN LPAD(X.DNI_RUC, 12, '0')
                    ELSE X.DNI_RUC
                END DNI_RUC,
                ROW_NUMBER() OVER (PARTITION BY X.DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM {$sourceTable} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
                ON X.CUSTOMER_ID_CODCLI = Y.CUSTOMER_ID
            JOIN (
                SELECT * FROM {$devolucionTable}
                WHERE FAMILIA = 'Acceso Dedicado a Internet'
            ) Z
                ON Y.CODCLI = Z.CODCLI
                AND TO_NUMBER(NULLIF(REGEXP_REPLACE(X.DNI_RUC, '[^0-9]+', ''), '')) = TO_NUMBER(NULLIF(REGEXP_REPLACE(Z.NRO_DOC, '[^0-9]+', ''), ''))
            WHERE X.SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
            {$cmtsFechaCondition}
            AND X.DESCRIPCION_PRODUCTO LIKE '%MBPS%'

            UNION ALL

            SELECT DISTINCT
                X.CUSTOMER_ID_CODCLI,
                X.FUENTE,
                X.SERIALNUMBER,
                X.NOMBRE,
                X.ID_CARD_TYPE_VALUE,
                X.TELEFONO_CLARO,
                X.NUMERO_ADICIONAL,
                X.SERVICIO_PRODUCTO,
                X.CORREO,
                X.DISTRITO,
                X.PROVINCIA,
                X.DEPARTAMENTO,
                X.DESCRIPCION_PRODUCTO,
                X.AGREEMENT_STATUS,
                X.AGREEMENT_STATUS_DATE,
                X.AGREEMENT_START_DATE,
                X.AGREEMENT_END_DATE,
                X.INSTALLATION_MAP,
                X.NUMERO,
                CASE
                    WHEN LENGTH(X.DNI_RUC) <= 8 AND REGEXP_REPLACE(X.DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(X.DNI_RUC, 12, '0')
                    WHEN LENGTH(X.DNI_RUC) < 8 THEN LPAD(X.DNI_RUC, 8, '0')
                    WHEN 8 < LENGTH(X.DNI_RUC) AND LENGTH(X.DNI_RUC) < 11 THEN LPAD(X.DNI_RUC, 12, '0')
                    ELSE X.DNI_RUC
                END DNI_RUC,
                ROW_NUMBER() OVER (PARTITION BY X.DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM {$sourceTable} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
                ON X.CUSTOMER_ID_CODCLI = Y.CUSTOMER_ID
            JOIN (
                SELECT * FROM {$devolucionTable}
                WHERE TRIM(FAMILIA) = 'Cable'
            ) Z
                ON Y.CODCLI = Z.CODCLI
                AND TO_NUMBER(NULLIF(REGEXP_REPLACE(X.DNI_RUC, '[^0-9]+', ''), '')) = TO_NUMBER(NULLIF(REGEXP_REPLACE(Z.NRO_DOC, '[^0-9]+', ''), ''))
            WHERE X.SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
            {$cmtsFechaCondition}
            AND X.DESCRIPCION_PRODUCTO LIKE '%TV%'
        )
        WHERE ORDEN = 1";
    }

    private function tableName(string $baseName): string
    {
        return "USRAES.{$baseName}_{$this->userIdentifier}";
    }

    private function sanitizeOracleSuffix($value): string
    {
        $suffix = strtoupper(preg_replace('/[^A-Z0-9_]/i', '_', (string) $value));
        $suffix = trim($suffix, '_');

        if ($suffix === '') {
            throw new Exception('No se pudo determinar el identificador del usuario para tablas temporales');
        }

        return substr($suffix, 0, 30);
    }

    private function dropTableIfExists(string $tableName): void
    {
        try {
            DB::connection("oracle")->statement("DROP TABLE {$tableName} PURGE");
        } catch (\Throwable $th) {
            if (!$this->isOracleObjectMissing($th)) {
                throw $th;
            }
        }
    }

    private function dropIndexIfExists(string $indexName): void
    {
        try {
            DB::connection("oracle")->statement("DROP INDEX {$indexName}");
        } catch (\Throwable $th) {
            if (!$this->isOracleObjectMissing($th)) {
                throw $th;
            }
        }
    }

    private function isOracleObjectMissing(\Throwable $th): bool
    {
        return strpos($th->getMessage(), 'ORA-00942') !== false
            || strpos($th->getMessage(), 'ORA-01418') !== false
            || strpos($th->getMessage(), 'ORA-04043') !== false;
    }

    public function getOltReport(DateTime $fecha, array $olts)
    {
        $this->generateReport(1, $fecha, $olts);
        return DB::connection("oracle")->select(DB::raw("SELECT 
        CUSTOMER_ID_CODCLI,
        FUENTE,
        SERIALNUMBER,
        NOMBRE,
        ID_CARD_TYPE_VALUE,
        TELEFONO_CLARO,
        NUMERO_ADICIONAL,
        SERVICIO_PRODUCTO,
        CORREO,
        DISTRITO,
        PROVINCIA,
        DEPARTAMENTO,
        DESCRIPCION_PRODUCTO,
        AGREEMENT_STATUS,
        AGREEMENT_STATUS_DATE,
        AGREEMENT_START_DATE,
        AGREEMENT_END_DATE,
        INSTALLATION_MAP,
        NUMERO, 
        CASE
        WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
        WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
        WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
        ELSE DNI_RUC
        END DNI_RUC FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier}"));
    }

    public function getCmtsReport(DateTime $fecha, array $cmts)
    {
        $this->generateReport(2, $fecha, $cmts);
        return DB::connection("oracle")->select(DB::raw("SELECT 
        CUSTOMER_ID_CODCLI,
        FUENTE,
        SERIALNUMBER,
        NOMBRE,
        ID_CARD_TYPE_VALUE,
        TELEFONO_CLARO,
        NUMERO_ADICIONAL,
        SERVICIO_PRODUCTO,
        CORREO,
        DISTRITO,
        PROVINCIA,
        DEPARTAMENTO,
        DESCRIPCION_PRODUCTO,
        AGREEMENT_STATUS,
        AGREEMENT_STATUS_DATE,
        AGREEMENT_START_DATE,
        AGREEMENT_END_DATE,
        INSTALLATION_MAP,
        NUMERO, 
        CASE
        WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
        WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
        WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
        ELSE DNI_RUC
        END DNI_RUC FROM USRAES.cmts_table_final_{$this->userIdentifier}"));
    }

    private function generateReport(int $type_id, DateTime $fecha, array $values)
    {
        $now = new DateTime();
        $diff = $fecha->diff($now);
        // $strValues = implode("','", $olts);
        $this->userIdentifier = $this->sanitizeOracleSuffix($this->authService->getUserIdentifier());
        

        $request = [
            "type_id" => $type_id,
            "days" => "{$diff->days}",
            "values" => $values
        ];
        $http_response = Http::withHeaders([
            "x-user-identifier" => $this->userIdentifier,
            // "Content-Type" => "application/json"
        ])
        ->timeout(-1)
        ->post(env("APP_API")."/olt-cmts/process", $request);

        if($http_response->getStatusCode() !== 200){
            $error_message = $http_response->body();
            throw new Exception(json_encode($error_message));
        }
    }

    public function getOltListSummary()
    {
        $query = "SELECT min(fecha) fecha_min, max(fecha) fecha_max FROM portal_autogestion.list_olt_1day";
        $result = DB::connection("ch-dn05")->select($query);
        return json_decode(json_encode($result[0]), false);
    }
}
