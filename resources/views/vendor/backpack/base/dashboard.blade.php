@extends(backpack_view('blank'))

@php
    use Backpack\CRUD\app\Library\Widget;

    //$html = '<a class="btn btn-danger" href="/portalmapafija/public/mapa" role="button">mapa</a>';

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
            $html = $html.' <a class="btn btn-danger" href="'.asset($url).'" role="button">'.$row->label.'</a>';
        }else{
            $html = $html.' <a class="btn btn-danger" href="#" role="button">'.$row->label.'</a>';
        }
    }

   $widgets['before_content'][] = [
        'type'        => 'jumbotron',
        'heading'     => (config('backpack.base.project_name') ? config('backpack.base.project_name') : 'Portal regulatorio'),
        'content'     => '',
        'buttons' => $html,
    ];
@endphp

@section('content')

@endsection