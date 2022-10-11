@extends(backpack_view('blank'))

@php
    use Backpack\CRUD\app\Library\Widget;
    use Illuminate\Support\Facades\DB;

    $auth_service = app(\AMovil\Auth\AccessControl\Domain\AuthService::class);
    $service = app(\AMovil\Auth\User\Services\GetUserModules::class);

    $username = $auth_service->getUserIdentifier();
    $response = $service->__invoke($username);
    $modules = $response['tree'];
    $modules_by_id = $response['by_id'];

    $html = '';
    foreach($modules as $row){
        $url = null;
        if(count($row->tracings)>0){
            $id_tracing_to_find = $row->tracings[0];
            $url = $modules_by_id[$id_tracing_to_find]->url;
        }
        if($url !== null){
            $html = $html.' <a class="btn btn-danger" href="'.asset($url).'" role="button">'.$row->trac_name.'</a>';
        }else{
            $html = $html.' <a class="btn btn-danger" href="#" role="button">'.$row->trac_name.'</a>';
        }
    }
    
    //$usuario = backpack_user()->usuario;
    //dd($usuario);
    //$perfiles = DB::table('padm_usuarioperfil')->where('usuario', $usuario)->where('estado', 1)->select('perfil')->get();
    //$prg_seguimiento = "usraes.prg_seguimiento";
    /*
    $prg_seguimiento = env('APP_ENV') === 'local' ? 'prg_seguimiento' : "usraes.prg_seguimiento";
    $kpis = DB::table($prg_seguimiento)->where('trac_description', 'kpi')->get();
    foreach($kpis as $kpi){
        $first_menu = DB::table($prg_seguimiento)->where('trac_father', $kpi->id_tracing)->where('trac_status', 1)->first();
        if($first_menu !== null){
            if($first_menu->trac_description === 'submenu' || $first_menu->url === null){
                $first_menu = DB::table($prg_seguimiento)->where('trac_father', $first_menu->id_tracing)->where('trac_status', 1)->first();
                //dd($first_menu); 
            }
            
            $html = $html.' <a class="btn btn-danger" href="'.asset($first_menu->url).'" role="button">'.$kpi->trac_name.'</a>';
        }else{
            $html = $html.' <a class="btn btn-danger" href="#" role="button">'.$kpi->trac_name.'</a>';
        }
    }*/

    /*foreach($perfiles as $perfil){

        $kpis = DB::table('prg_seguimiento')->join('PADM_TYPE', 'prg_seguimiento.id_tracing','PADM_TYPE.type_description')->where('PADM_TYPE.TYPE_NAME', $perfil->perfil)->where('prg_seguimiento.trac_description', 'kpi')->where('PADM_TYPE.type_father',781)->where('PADM_TYPE.status',1)->where('trac_status', 1)->where('status', 1)->orderBy('trac_order')->select('trac_name','id_type')->get();

        foreach($kpis as $kpi){
            $type_name = DB::table('PADM_TYPE')->where('TYPE_FATHER', $kpi->id_type)->where('status', 1)->orderBy('id_type')->first();
            if(isset($type_name )){
                $menu = DB::table('prg_seguimiento')->where('id_tracing', $type_name->type_name)->where('trac_status', 1)->get('url')->first();
                $html = $html.' <a class="btn btn-danger" href="'.backpack_url($menu->url).'" role="button">'.$kpi->trac_name.'</a>';
            }else{
                $html = $html.' <a class="btn btn-danger" href="#" role="button">'.$kpi->trac_name.'</a>';
            }

        }
    }*/

   $widgets['before_content'][] = [
        'type'        => 'jumbotron',
        'heading'     => (config('backpack.base.project_name') ? config('backpack.base.project_name') : 'Portal regulatorio'),
        'content'     => 'Network Performance',
        'buttons' => $html,
    ];
@endphp

@section('content')

@endsection