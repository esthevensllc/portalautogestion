<?php

namespace AMovil\Reports\IMRIMF\Controllers;

use DateTime;
use Illuminate\Http\Request;

class ImfMovilController
{

    public function __construct()
    {

    }
    
    public function view()
    {
        $config = [
            'title' => 'Consulta de Acciones IMF MOVIL',
            'title_percent' => 'Porcentaje de uso de IMF'
        ];
        return view("imrimf.imf_movil", compact("config"));
    }

}
