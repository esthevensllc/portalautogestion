<?php

namespace AMovil\Reports\DetallePlanes\FacturacionDetallada\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DetallePlanes\FacturacionDetallada\Domain\FacturacionDetalladaRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentFacturacionDetalladaRepository implements FacturacionDetalladaRepository
{
    private $connection = "oracle";
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function getByNumCuentaAndPeriodo(array $numerosCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $strPeriodo = $periodo->format("Ym");

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.REP_FDETALLADA_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.REP_FDETALLADA_{$this->userIdentifier}(NUM_CUENTA VARCHAR2(100))"];

        $this->exec_sql($queries);

        foreach($numerosCuenta as $numCuenta){
            DB::table("USRAES.REP_FDETALLADA_{$this->userIdentifier}")
            ->insert(["num_cuenta" => $numCuenta]);
        }

        $query = "SELECT d.nro_factura,A.CUENTA,
        case
        when upper(a.nro_telefono) <> 'TOTAL' then
        a.Ciclo ||
        to_char((TRUNC(d.FCH_EMISION, 'MM') -
                (- (d.FCH_EMISION - 1 - TRUNC(d.FCH_EMISION, 'MM')) + 30)),
                '/MM/YYYY') || '-' ||
        to_char(d.FCH_EMISION - 1, 'DD/MM/YYYY')
        else
        null
        end Fecha_inicio_Fecha_Fin,
        NRO_TELEFONO,
        (select count(distinct NRO_TELEFONO)
        from DWA.STD_FACTURA_DET
        where CUENTA  IN (SELECT NUM_CUENTA FROM USRAES.REP_FDETALLADA_{$this->userIdentifier}) 
            and trim(PERIODO) = '{$strPeriodo}'
            AND upper(NRO_TELEFONO) NOT in ('TOTAL', 'VARIOS')
            AND upper(NRO_TELEFONO) NOT LIKE '%LINEAS') CANTIDAD_LINEAS,
        (SELECT sum(count(DISTINCT r.des))
        FROM dmred.directory_number dn,
                dmred.contr_services_cap csc,
                dmred.contract_all c,
                dmred.rateplan r,
                dmred.customer_all t,
                (select distinct NRO_TELEFONO
                from DWA.STD_FACTURA_DET
                where CUENTA  IN (SELECT NUM_CUENTA FROM USRAES.REP_FDETALLADA_{$this->userIdentifier} ) 
                    and trim(PERIODO) = '{$strPeriodo}'
                    AND upper(NRO_TELEFONO) <> 'TOTAL') f
        where t.custcode  IN ( SELECT NUM_CUENTA FROM USRAES.REP_FDETALLADA_{$this->userIdentifier} ) 
            and c.customer_id = t.customer_id
            AND csc.dn_id = dn.dn_id(+)
            AND c.tmcode = r.tmcode
            AND csc.co_id = c.co_id
            AND csc.main_dirnum = 'X'
            and csc.cs_deactiv_date is null
            and dn.dn_num = f.NRO_TELEFONO
        group by r.des) CANTIDAD_PLANES,
        b.des Plan,
        (a.G_TCARGODELMES + a.G_TOTROSCARGOSNOAFECTOSALIGV +
        a.G_TCOBRANZASDIFERIDAS) TOTAL_CARGO_MES,
        a.CARGOSVOZ_plan CARGOS_FIJO_VOZ,
        (CARGOSVAS_GPRS + cargosvas_paquetededatos) CARGO_FIJO_DATOS,
        (otroscargosvoz_clarodirecto + otroscargosvoz_rpc +
        otroscargosvoz_rpce + otroscargosvoz_claroconnection +
        otroscargosvoz_clarohabilita) CARGOS_ADICIONALES_VOZ,
        (cargosvas_clarodata + cargosvas_gprswapilimitado +
        cargosvas_gprswebilimitado + cargosvas_internetmovil) CARGOS_ADICIONALES_DATOS,
        traficoldnldi_ldi CARGO_LDI,
        traficoroaming_traficoroaming CARGO_ROAMING,
        (equipos_prestamo + equipos_reposicion) CARGO_POR_EQUIPOS,
        (otros_otros + otroscya_otroscya) OTROS_CARGO_ABONOS,
        a.G_CARGOSDELMES TOTAL_CARGO_MES_SIGV,
        a.G_IGV IGV,
        a.G_TCARGODELMES TOTAL_CARGO_MES_CIGV,
        a.G_TOTROSCARGOSNOAFECTOSALIGV T_OTROS_CARGOS_NAFECT_IGV,
        a.G_TCOBRANZASDIFERIDAS TOTAL_COBRANZAS_DIFERIDAS,
        (a.CARGOSVAS_BOLSAS + a.CARGOSVAS_BANDAANCHAMOVILBB +
        a.CARGOSVAS_CALLERIDPRIVADO + a.CARGOSVAS_BES + a.CARGOSVAS_BES_BIS +
        a.CARGOSVAS_BIS + a.CARGOSVAS_CORREO + a.CARGOSVAS_LBSWEB +
        a.CARGOSVAS_CLARODATA + a.CARGOSVAS_CLARODATAHS +
        a.CARGOSVAS_FACTDETMODA + a.CARGOSVAS_GPRS +
        a.CARGOSVAS_GPRSWAPILIMITADO + a.CARGOSVAS_GPRSWEBILIMITADO +
        a.CARGOSVAS_INTELLISYNC + a.CARGOSVAS_INTERNETMOVIL +
        a.CARGOSVAS_LBS + a.CARGOSVAS_PAQUETEDEDATOS +
        a.CARGOSVAS_PAQUETESMS + a.CARGOSVAS_PAQUETEVIDEOLLAMADA +
        a.CARGOSVAS_SMS_MAIL + a.CARGOSVAS_SMSONNETILIMITADO +
        a.CARGOSVAS_SOLUCIONSMS + a.CARGOSVAS_MAIL2SMS +
        a.CARGOSVAS_RESCATELPLUS + a.CARGOSVAS_ACCESOMESVENCIDO +
        a.CARGOSVAS_ACCESONORMAL + a.CARGOSVAS_ACCESOPROMO +
        a.CARGOSVAS_ACCESOREDUCIDO10P + a.CARGOSVAS_ACCESOREDUCIDO5P +
        a.CARGOSVAS_ASISTENCIAVIAJERA + a.CARGOSVAS_BONOVAS +
        a.CARGOSVAS_BISSOCIAL + a.CARGOSVAS_TRY_BUY +
        a.CARGOSVAS_CLAROBANCA + a.CARGOSVAS_MBILIMITADOS +
        a.CARGOSVAS_MAIL + a.CARGOSVAS_PAQUETEMINUTOS +
        a.CARGOSVAS_PAQUETEMINUTOSYSMS + a.CARGOSVAS_PCASISTENCIA +
        a.CARGOSVAS_ROAMINGDATOS + a.CARGOSVAS_TRIATE +
        a.CARGOSVAS_CBLACKBERRYMAXMESSG + a.CARGOSVAS_BANDAANCHAMOVIL +
        a.ADICIONALVAS_SMS + a.ADICIONALVAS_MMS + a.ADICIONALVAS_GPRS +
        a.ADICIONALVAS_PREMIUM + a.ADICIONALVAS_CLAROPROTECCION) CARGO_VAS,
        a.TRAFICOLDNLDI_LDN CARGO_LDN
        from DWA.STD_FACTURA_DET a
        left join (SELECT distinct r.des, t.custcode, dn.dn_num, c.customer_id
            FROM dmred.directory_number   dn,
                dmred.contr_services_cap csc,
                dmred.contract_all       c,
                dmred.rateplan           r,
                dmred.customer_all       t
            where t.custcode  IN (SELECT NUM_CUENTA FROM USRAES.REP_FDETALLADA_{$this->userIdentifier} ) 
            and c.customer_id = t.customer_id
            AND csc.dn_id = dn.dn_id(+)
            AND c.tmcode = r.tmcode
            AND csc.co_id = c.co_id
            AND csc.main_dirnum = 'X'
            AND TO_CHAR(CS_ACTIV_DATE, 'YYYYMM') <= '{$strPeriodo}'
            AND (TO_CHAR(CS_DEACTIV_DATE, 'YYYYMM') >= '{$strPeriodo}' OR
                CS_DEACTIV_DATE IS NULL)) b
            on (trim(a.cuenta) = trim(b.custcode) and a.NRO_TELEFONO = b.dn_num )
        left join (SELECT distinct DOCUMENTO     AS nro_factura,
        FECHA_EMISION AS FCH_EMISION,
        CUSTOMER_ID
        FROM DWA.F_M_FACTURACION
        WHERE periodo = '{$strPeriodo}' ) d
        on b.customer_id = d.CUSTOMER_ID
        where trim(PERIODO) = '{$strPeriodo}'  -- '{$strPeriodo}'
            and CUENTA in (SELECT NUM_CUENTA FROM USRAES.REP_FDETALLADA_{$this->userIdentifier}) --PI_CUENTA
        ORDER BY 2,4 DESC";

        $data = DB::select(DB::raw($query));

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.REP_FDETALLADA_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $this->exec_sql($queries);

        return $data;
    }

    public function getTiposInput()
    {
        $data = [];
        $data[] = ["id" => 1, "label" => "NUMERO DE CUENTA"];
        $data[] = ["id" => 2, "label" => "EXCEL"];
        return json_decode(json_encode($data), false);
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
