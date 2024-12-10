<?php
namespace App\Http\Controllers\FacturacionFija;

use AMovil\Auth\AccessControl\Domain\AuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Log\logReport;
use Carbon\Carbon;
use App\Exports\facturacionFija\FacturacionFijaExport;
use App\Exports\facturacionFija\FacturacionFijaSFExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class FacturacionFijaController extends Controller {
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function index(){
        $data = [
            'title' => 'Facturación Fija / Saliente'
        ];
        return view('backpack::facturacion_fija.saliente', compact('data'));
    }

    public function index_sf(){
        $data = [
            'title' => 'Facturación Fija / Saliente Sin Factura'
        ];
        return view('backpack::facturacion_fija.saliente_sf', compact('data'));
    }

    public function validReport(Request $request){
        $v_cod_clie = $request->get('cod_cliente');
        $v_fecha_ini = $request->get('f_ini');
        $v_fecha_fin = $request->get('f_fin');

        while(strlen($v_cod_clie)<8){
            $v_cod_clie = '0'.$v_cod_clie;
        }

        $result = DB::select(DB::RAW("SELECT COUNT(*) AS cliente FROM ( SELECT  distinct codcli FROM dws.SA_BILFAC WHERE codcli='$v_cod_clie')"));  

        if((int)$result[0]->cliente<1){
            return -1;
        }

        $result = DB::select(DB::RAW("SELECT COUNT(*) as cliente 
                                        FROM (
                                        SELECT CODCLI,trunc(horaini) 
                                        FROM DWS.SA_BILVALCDR 
                                        WHERE CODCLI='$v_cod_clie' AND trunc(horaini)>=to_date('$v_fecha_ini','dd/mm/yyyy') and trunc(horaini)<=to_date('$v_fecha_fin','dd/mm/yyyy') GROUP BY CODCLI,trunc(horaini)
                                        )"));

        if((int)$result[0]->cliente<1){
            return 0;
        }

        return 1;

    }

    public function generar_reporte(Request $request){
        ini_set('max_execution_time', 1800);
        $this->userIdentifier = $this->authService->getUserIdentifier();
        //$now = Carbon::now();
        //$v_log_id = (int) $now->format('YmdHis');
        $v_cod_clie = $request->get('cod_cliente');
        $v_fecha_ini = $request->get('f_ini');
        $v_fecha_fin = $request->get('f_fin');

        while(strlen($v_cod_clie)<8){
            $v_cod_clie = '0'.$v_cod_clie;
        }     

        $resp = [];
        $plsql = [];
        // $plsql[] = "drop table USRAES.TB_FIJA_FACTURADA";
        $plsql[] = "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TB_FIJA_FACTURADA_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;";
        $plsql[] = "CREATE TABLE USRAES.TB_FIJA_FACTURADA_{$this->userIdentifier} as(
                select distinct a.codcli, a.numser serie_recibo, a.numsut num_recibo, ani telefono_origen, 
                dscisdest telefono_destino,dscserv servicio, b.nomdes nombre_destino,
                tipdest tipo_destino,
                TO_CHAR(horaini, 'DD/MM/YYYY HH24:MI:SS') horaini, 
                TO_CHAR(horafin, 'DD/MM/YYYY HH24:MI:SS') horafin,
                cantidad minutos,
                TRUNC((horafin - horaini) * (60 * 60 * 24)) + 1 segundos, idtiphor, 
                tarifa, monto, a.nomcli,b.idcon, b.idoperador, c.descripcion Operador,
                b.idclaisdest, d.descripcion Clase_Destino, b.idgrpdes, e.descripcion Grupo_Destino,cantidadval, 
                cantidadorigen
                from dws.SA_BILFAC a, DWS.SA_BILVALCDR b, DWS.SA_OPERADOR c, DWS.SA_CLASEISDESTINO d,
                DWS.SA_GRUPODESTINO e                
                where 
                a.idbilfac=b.idbilfac
                and c.idoperador=b.idoperador(+)
                and d.idclaisdest=b.idclaisdest(+)
                and e.idgrpdes=b.idgrpdes(+)
                and a.codcli='{$v_cod_clie}'
                and trunc(horaini)>=to_date('{$v_fecha_ini}','dd-mm-yyyy')
                and trunc(horaini)<=to_date('{$v_fecha_fin}','dd-mm-yyyy')
                )";

        $resp = [];
        foreach($plsql as $sql){
            $resp[] = DB::statement(DB::Raw($sql));
        }

        $result = DB::select(DB::RAW("select telefono_origen from USRAES.TB_FIJA_FACTURADA_{$this->userIdentifier} where rownum = 1"));
        if(isset($result[0])){
            $tel_fijo = $result[0]->telefono_origen;
        }else{
            $tel_fijo = '';
        }

        $now = Carbon::now(); 

        /*logReport::insert([
            'hostname' => 'limnwkdaswfv01',
            'name' => 'FACTURACION FIJA',
            'direccion' => '',
            'area' => 'Control Regulatorio',
            'contacto' => \Auth::guard(backpack_guard_name())->user()->name,
            'responsable' => 'DIEGO MORENO',
            'file' => 'Facturacion_Fija_'.$tel_fijo.'_'.$now->format('Ymd').$now->format('H:i:s'),
            'ini' => $v_fecha_ini,
            'fin' => $v_fecha_fin,
            'lat' => 0,
            'estado' => 1,
            'mensaje' => 'se generó el archivo'
        ]);*/

        return Excel::download(new FacturacionfijaExport($this->authService), 'facturacion_fija.xlsx');
    }

    public function generar_reporte_sf(Request $request){
        $this->userIdentifier = $this->authService->getUserIdentifier();
        //$now = Carbon::now();
        //$v_log_id = (int) $now->format('YmdHis');
        $v_cod_clie = $request->get('cod_cliente');
        $v_fecha_ini = $request->get('f_ini');
        $v_fecha_fin = $request->get('f_fin');

        while(strlen($v_cod_clie)<8){
            $v_cod_clie = '0'.$v_cod_clie;
        }     

        $resp = [];
        $plsql = [];
        // $plsql[] = "drop table USRAES.TB_FIJA_FACTURADA";
        $plsql[] = "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;";
        $plsql[] = "CREATE TABLE USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier} as(
                SELECT distinct codcli,
                    ani telefono_origen,
                    dscisdest telefono_destino,
                    dscserv servicio,
                    b.nomdes nombre_destino,
                    tipdest tipo_destino,
                    TO_CHAR(horaini, 'DD/MM/YYYY HH24:MI:SS') horaini,
                    TO_CHAR(horafin, 'DD/MM/YYYY HH24:MI:SS') horafin,
                    cantidad minutos,
                    TRUNC((horafin - horaini) * (60 * 60 * 24)) + 1 segundos,
                    idtiphor,
                    tarifa, monto, b.idcon, b.idoperador, c.descripcion Operador,
                    b.idclaisdest, d.descripcion Clase_Destino, b.idgrpdes, e.descripcion Grupo_Destino,cantidadval,
                    cantidadorigen
                from DWS.SA_BILVALCDR b, DWS.SA_OPERADOR c,
                    DWS.SA_CLASEISDESTINO d, DWS.SA_GRUPODESTINO e
                where c.idoperador=b.idoperador(+)
                    and d.idclaisdest=b.idclaisdest(+)
                    and e.idgrpdes=b.idgrpdes(+)
                    and CODCLI = '{$v_cod_clie}'
                    AND TO_CHAR(TRUNC(HORAINI),'YYYYMMDD') >= to_date('{$v_fecha_ini}','dd/mm/yyyy')
                    AND TO_CHAR(TRUNC(HORAINI),'YYYYMMDD') <= to_date('{$v_fecha_fin}','dd/mm/yyyy')
                )";

        $resp = [];
        foreach($plsql as $sql){
            $resp[] = DB::statement(DB::Raw($sql));
        }

        $result = DB::select(DB::RAW("select telefono_origen from USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier} where rownum = 1"));
        if(isset($result[0])){
            $tel_fijo = $result[0]->telefono_origen;
        }else{
            $tel_fijo = '';
        }

        $now = Carbon::now(); 

        /*logReport::insert([
            'hostname' => 'limnwkdaswfv01',
            'name' => 'FACTURACION FIJA',
            'direccion' => '',
            'area' => 'Control Regulatorio',
            'contacto' => \Auth::guard(backpack_guard_name())->user()->name,
            'responsable' => 'DIEGO MORENO',
            'file' => 'Facturacion_Fija_'.$tel_fijo.'_'.$now->format('Ymd').$now->format('H:i:s'),
            'ini' => $v_fecha_ini,
            'fin' => $v_fecha_fin,
            'lat' => 0,
            'estado' => 1,
            'mensaje' => 'se generó el archivo'
        ]);*/

        return Excel::download(new FacturacionFijaSFExport($this->authService), 'facturacion_fija.xlsx');
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
        $data = DB::connection('oracle')->table('reporte_sigrei_tmp')->get();
        return response()->json($data);
    }
}