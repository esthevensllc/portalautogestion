<?php

namespace AMovil\Reports\CajaArequipa\GBOtorgados\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\CajaArequipa\GBOtorgados\Domain\GBOtorgadosRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentGBOtorgadosRepository implements GBOtorgadosRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getByNumCuentaAndPeriodo($numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $dt_fecha_ini = (clone $periodo);
        $dt_fecha_ini->modify("-1 month");

        $str_periodo = $periodo->format("Ym");

        DB::statement(DB::raw("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.BASE_NUM_AREQ_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"));
        DB::statement(DB::raw("CREATE TABLE USRAES.BASE_NUM_AREQ_{$this->userIdentifier}(numeros VARCHAR2(50), ciclo VARCHAR2(2))"));
        DB::statement(DB::raw("INSERT INTO USRAES.BASE_NUM_AREQ_{$this->userIdentifier}
        select s.subscription_access_number numeros, CUSTOMER_ACCOUNT_BILLING_CYCLE_SC ciclo from DWA.DW_M_SUBSCRIPTION_HIST PARTITION (P_{$str_periodo}) s
        where S.CUSTOMER_ACCOUNT_DESC = ? AND s.subscription_status<> 'D'"), [$numCuenta]);

        $linea = DB::table("USRAES.BASE_NUM_AREQ_{$this->userIdentifier}")->first();

        $ciclo = null;
        if($linea !== null){
            $ciclo = $linea->ciclo;
        }else{
            return [];
        }
        $str_fecha_ini = $dt_fecha_ini->format("Ym").$ciclo;
        $str_fecha_fin = $periodo->format("Ym").$ciclo;

        DB::statement(DB::raw("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.otor_are_areq_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN
                RAISE;
            END IF;
        END;"));
        DB::statement(DB::raw("CREATE TABLE USRAES.otor_are_areq_{$this->userIdentifier} as 
                SELECT /*+ parallel(14)*/ co.co_id,co.dn_num,fh.fup_seq, 
                decode(sn.sncode,27,'Plan',sn.des) servicio,f.long_name, to_char(h.account_start_date, 'dd/mm/yyyy') inicio,
                to_char(h.account_end_date-1, 'dd/mm/yyyy') fin, 
                sum.des,
                h.fu_grant_interval U_Libres
        FROM   DWS.SA_FUP_ACCOUNTS_HEAD fh, 
        DMRED.MPUSNTAB sn, 
        DWS.SA_TIM_COFU_PARAMS c, 
        DWS.SA_fup_accounts_hist h, 
        DMRED.PR_SERV_STATUS_HIST ssh,
        DMRED.PP_DATOS_CONTRATO co,
        DWS.SA_FUP_ELEMENT_DEFINITION fup, 
        DWS.SA_FU_PACK f, 
        DWS.SA_MPSUMTAB sum
        WHERE  51||co.dn_num in (select distinct NUMEROS from USRAES.BASE_NUM_AREQ_{$this->userIdentifier}) --> NúmeroS q se quiere consultar
                AND sn.sncode = ssh.sncode
                AND h.account_start_date >= to_date('{$str_fecha_ini}','yyyymmdd') --Periodo de Inicio  
                AND h.account_start_date < to_date('{$str_fecha_fin}','yyyymmdd') --Periodo de Fin
                AND fh.co_id = co.co_id
                AND c.fu_pack_id = fh.fu_pack_id
                AND h.co_id = fh.co_id
                AND fh.account_key = h.account_key      
                AND h.balance_type = 'I'
                AND ssh.co_id = fh.co_id
                AND ssh.histno = (SELECT MAX(histno)
                                FROM   DMRED.PR_SERV_STATUS_HIST
                                WHERE  co_id = ssh.co_id
                                        AND sncode = ssh.sncode)
                AND ssh.sncode = c.sncode
                and c.fu_pack_id=fup.fu_pack_id
                and fup.fup_version=1
                and fh.fup_seq=fup.fup_seq
                and sum.umcode=fup.free_units_uom
                and c.fu_pack_id=f.fu_pack_id
                and SUM.UMCODE IN ('11','10','12','2')-- DATOS
        GROUP BY  co.co_id,to_char(h.account_start_date,'YYYYMM'),f.long_name,fh.fup_seq,sn.sncode,CO.dn_num,
        h.account_start_date,h.account_end_date-1,sn.des,SUM.DES,h.fu_grant_interval"));

        return DB::table("USRAES.otor_are_areq_{$this->userIdentifier}")->get();
    }
}
