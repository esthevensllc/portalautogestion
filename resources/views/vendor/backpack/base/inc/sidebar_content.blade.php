{{-- This file is used to store sidebar items, inside the Backpack admin panel --}}

@if(Route::currentRouteName() == 'backpack.dashboard')
    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
@else
    <?php
    $auth_service = app(\AMovil\Auth\AccessControl\Domain\AuthService::class);
    $service = app(\AMovil\Auth\User\Services\GetUserModules::class);

    $username = $auth_service->getUserIdentifier();
    $response = $service->__invoke($username);
    $modules = $response['tree'];
    $modules_by_id = $response['by_id'];
    $modules_by_url = $service->groupBy('url', $response['array']);

    $currentURL = Request::path();

    $main_module = [];
    foreach($modules as $row){
        $tracing = $modules_by_url[$currentURL];
        if(in_array($tracing->id_tracing, $row->tracings)){
            $main_module = $row->modulos;
        }
    }

    foreach($main_module as $row){
        if(isset($row->modulos)){
            echo "<li class='nav-item nav-dropdown' style='font-size: 12px;'>
                <a class='nav-link nav-dropdown-toggle' href='#'><i class='nav-icon {$row->icon}'></i>{$row->trac_name}</a>
                <ul class='nav-dropdown-items' style='padding-left: .5rem;'>
                <li class='nav-item'>";
            foreach($row->modulos as $sub_menu){
                echo "<a class='nav-link' href='".asset($sub_menu->url)."'>
                    <i class='nav-icon {$sub_menu->icon}'></i>
                    <span>".$sub_menu->trac_name."</span>
                </a>";
            }
            echo "</li>
                </ul>
            </li>";
        }else{
            echo "<li class='nav-item'><a class='nav-link' style='font-size: 12px;' href='".asset($row->url)."'><i class='".$row->icon."'></i>".$row->trac_name."</a></li>";
        }
    }



    /*$prg_seguimiento = env('APP_ENV') === 'local' ? 'prg_seguimiento' : "usraes.prg_seguimiento";
    $currentURL = Request::path();
    $menu_actual = DB::table($prg_seguimiento)->where('url', $currentURL)->first();
    if($menu_actual !== null){
        $menu_father = DB::table($prg_seguimiento)->where('id_tracing', $menu_actual->trac_father)->first();
        if($menu_father->trac_description === 'menu'){
            $menu_actual = $menu_father;
        }
        $menus = DB::table($prg_seguimiento)->where('trac_father', $menu_actual->trac_father)->orderBy('trac_order')->get();
        //dd($menus);
        foreach($menus as $menu){
            $sub_menus = DB::table($prg_seguimiento)->where('trac_father', $menu->id_tracing)->orderBy('trac_order')->get();
            if(count($sub_menus) === 0){
                echo "<li class='nav-item'><a class='nav-link' style='font-size: 12px;' href='".asset($menu->url)."'><i class='".$menu->icon."'></i>".$menu->trac_name."</a></li>";
            }else{
                echo "<li class='nav-item nav-dropdown' style='font-size: 12px;'>
                    <a class='nav-link nav-dropdown-toggle' href='#'><i class='nav-icon {$menu->icon}'></i>{$menu->trac_name}</a>
                    <ul class='nav-dropdown-items' style='padding-left: .5rem;'>
                    <li class='nav-item'>";
                foreach($sub_menus as $sub_menu){
                    echo "<a class='nav-link' href='".asset($sub_menu->url)."'>
                        <i class='nav-icon {$sub_menu->icon}'></i>
                        <span>".$sub_menu->trac_name."</span>
                    </a>";
                }
                echo "</li>
                    </ul>
                </li>";
            }
        }
    }*/
    ?>
@endif