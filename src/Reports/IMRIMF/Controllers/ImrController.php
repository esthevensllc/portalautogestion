<?php

namespace AMovil\Reports\IMRIMF\Controllers;

use DateTime;
use Illuminate\Http\Request;

class ImrController
{

    public function __construct()
    {

    }
    
    public function view()
    {
        $config = [
            'title' => 'Consulta de Acciones IMR',
            'title_percent' => 'Porcentaje de uso de IMF'
        ];
        return view("imrimf.imr", compact("config"));
    }

}
