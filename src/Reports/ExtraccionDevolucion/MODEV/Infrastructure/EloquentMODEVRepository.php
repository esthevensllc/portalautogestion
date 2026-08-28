<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeTipoReporte;
use AMovil\Reports\ExtraccionDevolucion\MODEV\Domain\MODEVRepository;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;
use Illuminate\Support\Facades\DB;

class EloquentMODEVRepository implements MODEVRepository
{
    private $db;
    private $authService;
    private $userIdentifier;

    public function __construct(ClickhouseDB $db, AuthService $authService)
    {
        $this->db = $db->connection("ch-dn04");
        $this->authService = $authService;
    }
    
    public function getReporteByTicketAndDepartamento($tipoReporte, $ticket, $departamento)
    {
        $extraFilter = "";
        if (in_array($tipoReporte, [InformeTipoReporte::BY_MSISDN, InformeTipoReporte::BY_MSISDN2])) {
            $extraFilter = " or aa.MODALIDAD_DEV LIKE '%WEB%'";
        }
        return DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT aa.ticket,
            aa.NRO_DOCUMENTO,
            aa.ID_CLIENTE,
            aa.MSISDN,
            'Comunicaciones Personales(PCS)' SERVICIO_AFECTADO,
            aa.MODO_CONTRATACION,
        ROUND(aa.CARGO_LINEA_IGV,2) monto_plan,
            bb.diferencia tiempo_averia,
            -- aa.MTO_DEV_FACTURACION MONTO_DEVOLVER_IGV,
	    CASE WHEN aa.MODALIDAD_DEV like '%POSTPAGO%' THEN aa.MTO_DEV_FACTURACION
	    WHEN aa.MODALIDAD_DEV like '%PREPAGO%' THEN aa.MTO_DEV
	    WHEN aa.MODALIDAD_DEV LIKE '%CF%' THEN ROUND(INTERES, 2) + ROUND(ROUND(ROUND(MONTO_DEVOLVER_IGV, 2)*1.18, 2) / 1.18, 2) WHEN aa.MODALIDAD_DEV like '%WEB%' and aa.MODALIDAD_DEV NOT LIKE '%CF%' THEN aa.MTO_TOTAL_DEV_IGV END MONTO_DEVOLVER_IGV,
            'SOLES' unidad,
	    case when aa.MSISDN_DEVOLVER is not null AND MODALIDAD_DEV LIKE '%POSTPAGO%' then aa.FECHA_DEVOLUCION
                when aa.MSISDN_DEVOLVER is not null AND MODALIDAD_DEV LIKE '%PREPAGO%' and FECHA_DEVOLUCION<>TO_DATE('01/01/1970','DD/MM/YYYY') then aa.FECHA_DEVOLUCION
                when aa.MSISDN_DEVOLVER is null and FECHA_DEVOLUCION=TO_DATE('01/01/1970','DD/MM/YYYY') then NULL END fecha_dev_fecha_comun,
            case when aa.MSISDN_DEVOLVER is not null then aa.FACTURA_APLICADA END FACTURA_APLICADA,
            case when aa.MSISDN_DEVOLVER is not null and aa.fecha_baja_facturacion is null then 'ACTIVO'
                WHEN aa.MSISDN_DEVOLVER is null or aa.fecha_baja_facturacion is not null then 'INACTIVO' END ESTADO,
            case when aa.MSISDN_DEVOLVER is null then aa.FECHA_BAJA
                when aa.fecha_baja_facturacion is not null then aa.fecha_baja_facturacion
                when aa.MSISDN_DEVOLVER is not null and aa.fecha_baja_facturacion is null then NULL END fecha_baja,
	    case when aa.MODALIDAD_DEV LIKE '%CF%' THEN aa.CUSTOMER_FULL_NAME when aa.MSISDN_DEVOLVER is null then aa.CUSTOMER_FULL_NAME 
                END NOMBRE_RAZON_SOCIAL,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'CENTRO DE ATENCIÃ“N AL CLIENTE'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN '' END LUGAR_DONDE_COBRAR,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'CONTAR CON DOCUMENTO DE IDENTIDAD'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN '' END REQUISITO_COBRO,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'SI'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN 'NO' END COMUNICACION,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'CORREO'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' then 'NO_APLICA' END MEDIO_COMUNICACION,
	    'NO_APLICA' NO_CORRESPONDE,
            CASE WHEN MODALIDAD_DEV like '%POSTPAGO%' and aa.MTO_DEV_FACTURACION is not null THEN 'DEVOLUCION APLICADA-POSTPAGO' 
		WHEN MODALIDAD_DEV like '%POSTPAGO%' and aa.MTO_DEV_FACTURACION is null THEN 'DEVOLUCION PENDIENTE-POSTPAGO'
            WHEN MODALIDAD_DEV like '%PREPAGO%' and aa.MTO_DEV is not null THEN 'DEVOLUCION APLICADA-PREPAGO' 
		WHEN MODALIDAD_DEV like '%PREPAGO%' and aa.MTO_DEV is null THEN 'DEVOLUCION PENDIENTE-PREPAGO'
            WHEN aa.MODALIDAD_DEV like '%CF%' THEN 'DEVOLUCION WEB - CARGO FIJO 0'
                WHEN aa.MODALIDAD_DEV like '%WEB%' THEN 'DEVOLUCION WEB' END COMENTARIOS,
		null liberado
        from USRAES.BASE_PREV_BASEDEV aa
        left join (
            SELECT ticket,corte_fecha_ini,corte_fecha_fin,(corte_fecha_fin-corte_fecha_ini)*24*60 diferencia
            FROM usraes.base_prev_basedev_input where ticket=:p1 and departamento= :p2  
            ) bb 
        on aa.ticket=bb.ticket and aa.FECHA_CORTE=bb.corte_fecha_ini
        where (aa.MODALIDAD_DEV LIKE '%POSTPAGO%' or aa.MODALIDAD_DEV LIKE '%PREPAGO%' {$extraFilter}) and aa.ticket= :p3 and departamento= :p4"), [
            "p1" => $ticket, "p2" => $departamento,
            "p3" => $ticket, "p4" => $departamento
        ]);
    }
    
    public function getReporteByTickets($tickets)
    {
        $ticketBinds = $this->getQueryBinds($tickets);
        return DB::select(DB::raw("
        with base_portal as (
            SELECT DISTINCT aa.ticket,
            aa.NRO_DOCUMENTO,
            aa.ID_CLIENTE,
            aa.MSISDN,
            'Comunicaciones Personales(PCS)' SERVICIO_AFECTADO,
            aa.MODO_CONTRATACION,
            ROUND(aa.CARGO_LINEA_IGV,2) monto_plan,
            bb.diferencia tiempo_averia,
            -- aa.MTO_DEV_FACTURACION MONTO_DEVOLVER_IGV,
            CASE WHEN aa.MODALIDAD_DEV like '%POSTPAGO%' THEN aa.MTO_DEV_FACTURACION
            WHEN aa.MODALIDAD_DEV like '%PREPAGO%' THEN aa.MTO_DEV
            WHEN aa.MODALIDAD_DEV LIKE '%CF%' THEN ROUND(INTERES, 2) + ROUND(ROUND(ROUND(MONTO_DEVOLVER_IGV, 2)*1.18, 2) / 1.18, 2) WHEN aa.MODALIDAD_DEV like '%WEB%' and aa.MODALIDAD_DEV NOT LIKE '%CF%' THEN aa.MTO_TOTAL_DEV_IGV END MONTO_DEVOLVER_IGV,
            'SOLES' unidad,
            case when aa.MSISDN_DEVOLVER is not null AND MODALIDAD_DEV LIKE '%POSTPAGO%' then aa.FECHA_DEVOLUCION
                when aa.MSISDN_DEVOLVER is not null AND MODALIDAD_DEV LIKE '%PREPAGO%' and FECHA_DEVOLUCION<>TO_DATE('01/01/1970','DD/MM/YYYY') then aa.FECHA_DEVOLUCION
                when aa.MSISDN_DEVOLVER is null and FECHA_DEVOLUCION=TO_DATE('01/01/1970','DD/MM/YYYY') then NULL END fecha_dev_fecha_comun,
	    case when aa.MSISDN_DEVOLVER is not null then aa.FACTURA_APLICADA END FACTURA_APLICADA,
            case when aa.MSISDN_DEVOLVER is not null and aa.fecha_baja_facturacion is null then 'ACTIVO'
                WHEN aa.MSISDN_DEVOLVER is null or aa.fecha_baja_facturacion is not null then 'INACTIVO' END ESTADO,
            case when aa.MSISDN_DEVOLVER is null then aa.FECHA_BAJA
		when aa.fecha_baja_facturacion is not null then aa.fecha_baja_facturacion
                when aa.MSISDN_DEVOLVER is not null and aa.fecha_baja_facturacion is null then NULL END fecha_baja,
            case when aa.MODALIDAD_DEV LIKE '%CF%' THEN aa.CUSTOMER_FULL_NAME when aa.MSISDN_DEVOLVER is null or aa.fecha_baja_facturacion is not null then aa.CUSTOMER_FULL_NAME
                END NOMBRE_RAZON_SOCIAL,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'CENTRO DE ATENCIÃ“N AL CLIENTE'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN '' END LUGAR_DONDE_COBRAR,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'CONTAR CON DOCUMENTO DE IDENTIDAD'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN '' END REQUISITO_COBRO,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'SI'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN 'NO' END COMUNICACION,
            case when aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV like '%WEB%' THEN 'CORREO'
            WHEN aa.fecha_baja_facturacion is not null or aa.MODALIDAD_DEV NOT LIKE '%WEB%' then 'NO_APLICA' END MEDIO_COMUNICACION,
	    'NO_APLICA' NO_CORRESPONDE,
            CASE WHEN MODALIDAD_DEV like '%POSTPAGO%' and aa.MTO_DEV_FACTURACION is not null THEN 'DEVOLUCION APLICADA-POSTPAGO'
                WHEN MODALIDAD_DEV like '%POSTPAGO%' and aa.MTO_DEV_FACTURACION is null THEN 'DEVOLUCION PENDIENTE-POSTPAGO'
            WHEN MODALIDAD_DEV like '%PREPAGO%' and aa.MTO_DEV is not null THEN 'DEVOLUCION APLICADA-PREPAGO'
                WHEN MODALIDAD_DEV like '%PREPAGO%' and aa.MTO_DEV is null THEN 'DEVOLUCION PENDIENTE-PREPAGO'
            WHEN aa.MODALIDAD_DEV like '%CF%' THEN 'DEVOLUCION WEB - CARGO FIJO 0'
                WHEN aa.MODALIDAD_DEV like '%WEB%' THEN 'DEVOLUCION WEB' END COMENTARIOS,
	    null LIBERADO,
            cc.TIPO_REPORTE TIPO_REPORTE,
            MODALIDAD_DEV, DEPARTAMENTO
            from USRAES.BASE_PREV_BASEDEV@DBL_REPTDM aa
            left join 
            (
                SELECT ticket,corte_fecha_ini,corte_fecha_fin,(corte_fecha_fin-corte_fecha_ini)*24*60 diferencia
                FROM usraes.base_prev_basedev_input@DBL_REPTDM
            ) bb
            on aa.ticket=bb.ticket and aa.FECHA_CORTE=bb.corte_fecha_ini
            LEFT JOIN 
            (
                SELECT A.TICKET,B.TIPO_REPORTE FROM usraes.noc_informe_de_fallas A
                JOIN USRAES.BASE_EXT_DEV_INPUT@DBL_REPTDM B
                ON A.NUMERO_DE_REPORTE=B.NUM_REPORTE
            ) CC
            ON AA.TICKET=CC.TICKET
            
        )
        select TICKET,NRO_DOCUMENTO,ID_CLIENTE,MSISDN,SERVICIO_AFECTADO,MODO_CONTRATACION,MONTO_PLAN,TIEMPO_AVERIA
        ,MONTO_DEVOLVER_IGV,UNIDAD,FECHA_DEV_FECHA_COMUN,FACTURA_APLICADA,ESTADO,FECHA_BAJA,NOMBRE_RAZON_SOCIAL
        ,LUGAR_DONDE_COBRAR,REQUISITO_COBRO,COMUNICACION,MEDIO_COMUNICACION,NO_CORRESPONDE,COMENTARIOS,LIBERADO 
        from base_portal
        where (MODALIDAD_DEV LIKE '%POSTPAGO%' or MODALIDAD_DEV LIKE '%PREPAGO%') and TIPO_REPORTE=1 and ticket in ({$ticketBinds['str_binds']})
        union all
        select TICKET,NRO_DOCUMENTO,ID_CLIENTE,MSISDN,SERVICIO_AFECTADO,MODO_CONTRATACION,MONTO_PLAN,TIEMPO_AVERIA
        ,MONTO_DEVOLVER_IGV,UNIDAD,FECHA_DEV_FECHA_COMUN,FACTURA_APLICADA,ESTADO,FECHA_BAJA,NOMBRE_RAZON_SOCIAL
        ,LUGAR_DONDE_COBRAR,REQUISITO_COBRO,COMUNICACION,MEDIO_COMUNICACION,NO_CORRESPONDE,COMENTARIOS,LIBERADO
        from base_portal
        where (MODALIDAD_DEV LIKE '%POSTPAGO%' or MODALIDAD_DEV LIKE '%PREPAGO%' or MODALIDAD_DEV LIKE '%WEB%')
        and TIPO_REPORTE=3 and ticket in ({$ticketBinds['str_binds']})"), $ticketBinds["values"]);
    }

    public function validateRecargas(array $ticket)
    {
        $ticketBinds = $this->getQueryBinds($ticket);

        $query = "SELECT
        ticket,
        fecha_carga,
        min(recharge_date) min_recharge_date,
        max(recharge_date) max_recharge_date,
        sum(if(served_number is NOT NULL,1,0)) cantidad_user_devueltos,
        sum(if(served_number is NULL,1,0)) cantidad_user_no_devueltos 
        from (
            select xx.*,yy.recharge_date,yy.served_number,yy.recharge_qty,yy.usage_str3 from 
            (
                select ticket,msisdn,msisdn_devolver,modalidad_dev,mto_total_dev_igv,toDate(substring(fecha_carga,1,10)) fecha_carga
                from  reptdm.base_prev_basedev
                where ticket in ({$ticketBinds['str_binds']})  and modalidad_dev like '%PREPAGO%' and length(ticket)=9 
            ) xx 
            left join (
            select aa.*,bb.*,row_number() over(partition by aa.ticket,aa.msisdn,aa.msisdn_devolver,aa.mto_total_dev_igv 
            order by bb.recharge_date asc) flag
            from 
            (
                select ticket,msisdn,msisdn_devolver,modalidad_dev,mto_total_dev_igv,toDate(substring(fecha_carga,1,10)) fecha_carga
                from  reptdm.base_prev_basedev
                where  ticket in ({$ticketBinds['str_binds']}) and modalidad_dev like '%PREPAGO%' and length(ticket)=9 
            ) as aa 
            left join recargas.recargas_dwo_osip as bb 
            on aa.msisdn_devolver=bb.served_number and bb.recharge_qty>0 and round(toFloat64(aa.mto_total_dev_igv)*100,2)=round(bb.recharge_qty,2)
            where date_diff('days',aa.fecha_carga,bb.recharge_date)<90 and date_diff('days',aa.fecha_carga,bb.recharge_date)>=0
            group by aa.*,bb.*
            ) yy 
            on xx.ticket=yy.ticket and xx.msisdn=yy.msisdn and xx.msisdn_devolver=yy.msisdn_devolver and xx.mto_total_dev_igv=yy.mto_total_dev_igv 
            and yy.flag=1
        ) group by 1,2
        having cantidad_user_no_devueltos>0 
        order by 2 desc";

        return $this->db->select($query, $ticketBinds["values"])->rows();
    }

    public function saveRecargasNoCorrectas($ticket){
        $query = "INSERT INTO recargas.recargas_dwo_osip
        select date_add(fecha_carga,1) recharge_date,
        msisdn_devolver served_number,
        round(toFloat64(mto_total_dev_igv)*100,2) recharge_qty,
        '92000559;Prepago_Devolucion_Interrupciones_Osiptel' usage_str3 
        from 
        (
            select xx.*,yy.recharge_date,yy.served_number,yy.recharge_qty,yy.usage_str3 
            from  ( 
                    select ticket,msisdn,msisdn_devolver,modalidad_dev,mto_total_dev_igv,toDate(substring(fecha_carga,1,10)) fecha_carga 
                    from  reptdm.base_prev_basedev 
                    where   ticket= :p_ticket and modalidad_dev like '%PREPAGO%' and length(ticket)=9
		            and load_date=(select toString(max(parseDateTimeBestEffort(load_date))) from reptdm.base_prev_basedev)
                    ) xx  
            left join ( 
                        select aa.*,bb.*,row_number() over(partition by aa.ticket,aa.msisdn,aa.msisdn_devolver,aa.mto_total_dev_igv  order by bb.recharge_date asc) flag 
                        from  ( 
                                select ticket,msisdn,msisdn_devolver,modalidad_dev,mto_total_dev_igv,toDate(substring(fecha_carga,1,10)) fecha_carga
                                from  reptdm.base_prev_basedev 
                                where   ticket= :p_ticket and modalidad_dev like '%PREPAGO%' and length(ticket)=9
				                and load_date=(select toString(max(parseDateTimeBestEffort(load_date))) from reptdm.base_prev_basedev)
                                ) aa  
                        left join recargas.recargas_dwo_osip bb  
                        on aa.msisdn_devolver=bb.served_number 
                            and bb.recharge_qty>0 and round(toFloat64(aa.mto_total_dev_igv)*100,2)=round(bb.recharge_qty,2) 
                        where date_diff('days',aa.fecha_carga,bb.recharge_date)<90 and date_diff('days',aa.fecha_carga,bb.recharge_date)>=0 
			            group by aa.*,bb.* 
                    ) yy  
        on xx.ticket=yy.ticket and xx.msisdn=yy.msisdn and xx.msisdn_devolver=yy.msisdn_devolver 
        and xx.mto_total_dev_igv=yy.mto_total_dev_igv  and yy.flag=1 having served_number is NULL
        )";

        $this->db->write($query, ["p_ticket" => $ticket]);
    }

    public function getReporteModev($tipoReporte, array $ticket){
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $extraFilter = "";
        if (in_array($tipoReporte, [InformeTipoReporte::BY_MSISDN, InformeTipoReporte::BY_MSISDN2])) {
            $extraFilter = "or aa.modalidad_dev like '%WEB%'";
        }

        $ticketBinds = $this->getQueryBinds($ticket);

        $inputMinutos = DB::connection("oracle_reptdm")->select("SELECT
        ticket, round((CORTE_FECHA_FIN - CORTE_FECHA_INI)*24*60) as minutos_corte
        from USRAES.BASE_PREV_BASEDEV_INPUT
        where ticket in ({$ticketBinds['str_binds']})
        and (ticket, departamento) in (
            select ticket, departamento from USRAES.BASE_PREV_BASEDEV_HIST
            group by ticket, departamento
        )", $ticketBinds['values']);

        // ("USRAES.BASE_PREV_BASEDEV_INPUT")
        // ->selectRaw("ticket, round((CORTE_FECHA_FIN - CORTE_FECHA_INI)*24*60) as minutos_corte")
        // ->whereIn("ticket", $ticket)
        // ->get();

        $this->db->write("DROP TABLE IF EXISTS default.base_extraccion_modev_input_{$this->userIdentifier}");

        $this->db->write("CREATE TABLE default.base_extraccion_modev_input_{$this->userIdentifier}(
            ticket String,
            minutos_corte Nullable(Int64)
        )
        ENGINE = MergeTree
        PRIMARY KEY ticket
        SETTINGS index_granularity = 8192");

        $data = [];

        foreach($inputMinutos as $row){
            $data[] = [$row->ticket, $row->minutos_corte];
        }

        if(count($data) > 0){
            $this->db->insert("default.base_extraccion_modev_input_{$this->userIdentifier}", $data, ["ticket", "minutos_corte"]);
        }


        $query = "SELECT 
        /*FORMATO MODEV*/
        aa.ticket ticket,
        aa.tipo_documento tipo_documento,
        aa.nro_documento nro_documento,
        aa.id_cliente id_cliente,
        aa.msisdn msisdn,
        'Comunicaciones Personales(PCS)' Servicio_Analizado,
        aa.modo_contratacion modo_contratacion,
        aa.cargo_linea_igv cargo_linea_igv,
        tinput.minutos_corte minutos,
        case when aa.modalidad_dev like '%POSTPAGO%' then if(aa.mto_dev_facturacion is not null, aa.mto_dev_facturacion, aa.mto_total_dev_igv)
            when aa.modalidad_dev like '%PREPAGO%' then aa.mto_total_dev_igv
        end monto_devolver,
        'SOLES' monedas,
        case when  aa.modalidad_dev like '%POSTPAGO%' then toDate(aa.fecha_devolucion)
            when aa.modalidad_dev like '%PREPAGO%' then bb.recharge_date
        end fecha_devolucion, 
        case when  aa.modalidad_dev like '%POSTPAGO%' then aa.factura_aplicada
            when aa.modalidad_dev like '%PREPAGO%' then ''
        end nro_recibo,
        'ACTIVO' estado,
        '' fecha_de_baja_del_servicio,
        aa.customer_full_name nombre_o_razon_social,
        '' lugar_donde_cobrar,
        '' requisitos_para_el_cobro,
        '' comunicacion,
        '' medio_de_comunicacion,
        'NO_APLICA' motivo_solo_cuando_no_corresponde,
        case when aa.modalidad_dev like '%POSTPAGO%' then 'DEVOLUCION APLICADA-POSTPAGO' 
            when aa.modalidad_dev like '%PREPAGO%' then 'DEVOLUCION APLICADA-PREPAGO' end comentarios,
        '' liberado,
        /*LOG DE DEVOLUCION DE PREPAGO*/
        bb.recharge_date recharge_date,
        bb.served_number served_number,
        bb.recharge_qty recharge_qty,
        bb.usage_str3 usage_str3,
        /*VALIDAR FECHA DE CARGA DEL REPORTE*/
        aa.fecha_carga fecha_carga
        from (select * from reptdm.base_prev_basedev where load_date=(select toString(max(parseDateTimeBestEffort(load_date))) from reptdm.base_prev_basedev)) aa 
        left join default.base_extraccion_modev_input_{$this->userIdentifier} as tinput
        on tinput.ticket = aa.ticket
        left join (
            select xx.*,yy.recharge_date,yy.served_number,yy.recharge_qty,yy.usage_str3 from 
            (
                select ticket,msisdn,msisdn_devolver,modalidad_dev,mto_total_dev_igv,toDate(substring(fecha_carga,1,10)) fecha_carga
                from  reptdm.base_prev_basedev
                where ticket in ({$ticketBinds['str_binds']}) and  modalidad_dev like '%PREPAGO%' and length(ticket)=9
		        and load_date=(select toString(max(parseDateTimeBestEffort(load_date))) from reptdm.base_prev_basedev) 
            ) xx 
            left join (
                select aa.*,bb.*,row_number() over(partition by aa.ticket,aa.msisdn,aa.msisdn_devolver,aa.mto_total_dev_igv 
                order by bb.recharge_date asc) flag
                from 
                (
                    select ticket,msisdn,msisdn_devolver,modalidad_dev,mto_total_dev_igv,toDate(substring(fecha_carga,1,10)) fecha_carga
                    from  reptdm.base_prev_basedev
                    where  ticket in ({$ticketBinds['str_binds']}) and  modalidad_dev like '%PREPAGO%' and length(ticket)=9
		            and load_date=(select toString(max(parseDateTimeBestEffort(load_date))) from reptdm.base_prev_basedev)
                ) as aa 
                left join recargas.recargas_dwo_osip as bb 
                on aa.msisdn_devolver=bb.served_number and bb.recharge_qty>0 and round(toFloat64(aa.mto_total_dev_igv)*100,2)=round(bb.recharge_qty,2)
                where date_diff('days',aa.fecha_carga,bb.recharge_date)<90 and date_diff('days',aa.fecha_carga,bb.recharge_date)>=0
                group by aa.*,bb.*
            ) yy 
            on xx.ticket=yy.ticket and xx.msisdn=yy.msisdn and xx.msisdn_devolver=yy.msisdn_devolver and xx.mto_total_dev_igv=yy.mto_total_dev_igv 
            and yy.flag=1
        ) bb 
        on aa.ticket=bb.ticket and aa.msisdn=bb.msisdn and aa.msisdn_devolver=bb.msisdn_devolver 
        /* where aa.ticket='202322137' and (modalidad_dev like '%POSTPAGO%' or modalidad_dev like '%PREPAGO%')*/

        where aa.ticket in ({$ticketBinds['str_binds']})
        and (aa.modalidad_dev like '%POSTPAGO%' or aa.modalidad_dev like '%PREPAGO%' {$extraFilter})
        group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19.20,21,22,23,24,25,26,27,28";

        $result = $this->db->select($query, $ticketBinds['values'])->rows();
        $this->db->write("DROP TABLE IF EXISTS default.base_extraccion_modev_input_{$this->userIdentifier}");

        return $result;
    }

    private function getQueryBinds(array $tickets){
        $str_binds = [];
        $bind_values = [];
        foreach($tickets as $index => $value){
            $str_binds[] = ":p_ticket_{$index}";
            $bind_values["p_ticket_{$index}"] = $value;
        }
        $str_binds = implode(", ", $str_binds);
        return [
            "str_binds" => $str_binds,
            "values" => $bind_values
        ];
    }
}
