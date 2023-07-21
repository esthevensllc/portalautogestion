@extends(backpack_view('blank'))

@section('header')
<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css"
  integrity="sha512-M2wvCLH6DSRazYeZRIm1JnYyh22purTM+FDB5CsyxtQJYeKq83arPe5wgbNmcFXGqiSH2XR8dT/fJISVA1r/zQ=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js"
integrity="sha512-lInM/apFSqyy1o6s89K4iQUKg6ppXEgsVxT35HbzUupEVRh2Eu9Wdl4tHj7dZO0s1uvplcYGmt3498TtHq+log=="
crossorigin=""></script>
<script src="/portalmonitoreonoc/js/PruneCluster.js"></script>
@endsection

@section('after_styles')
<style>
      body{
        background: #ebedf0;
      font-family: Source Sans Pro,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;
      }
      /* Set the size of the div element that contains the map */
      #map {
          height: 850px;
          /* The height is 400 pixels */
          width: 100%;
          /* The width is the width of the web page */
          border-radius: 10px;
          z-index: 0; 
      }

      .ruler-map{
        display: none!important;
        z-index: 1;
        top: 29px;
        left: 53px;
        background: none padding-box rgb(255, 255, 255);
        display: inline-block;
        border: 0px;
        border-radius: 0;
        margin: 0px;
        padding: 1px 6px;
        text-transform: none;
        appearance: none;
        position: relative;
        cursor: pointer;
        user-select: none;
        direction: ltr;
        overflow: hidden;
        text-align: left;
        color: rgb(86, 86, 86);
        font-family: Roboto, Arial, sans-serif;
        font-size: 15px;
        border-bottom-right-radius: 2px;
        border-top-right-radius: 2px;
        box-shadow: rgb(0 0 0 / 30%) 0px 1px 4px -1px;
      }

      .ruler-map:hover{
        background: none padding-box rgb(235, 235, 235);
        color: rgb(0, 0, 0);
      }

      .ruler-map.active{
        outline: 5px auto -webkit-focus-ring-color;
      }

      .gm-style .controls {
        font-size: 28px;
        /* this adjusts the size of all the controls */
        background-color: white;
        box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 4px -1px;
        box-sizing: border-box;
        border-radius: 2px;
        cursor: pointer;
        font-weight: 300;
        height: 1em;
        margin: 6px;
        text-align: center;
        user-select: none;
        padding: 2px;
        width: 1em;
      }

      .gm-style .controls button {
        border: 0;
        background-color: white;
        color: rgba(0, 0, 0, 0.6);
      }

      .gm-style .controls button:hover {
        color: rgba(0, 0, 0, 0.9);
      }

      .gm-style .controls.zoom-control {
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        height: auto;
      }

      .gm-style .controls.zoom-control button {
        font: 0.85em Arial;
        margin: 1px;
        padding: 0;
      }

      .gm-style .controls.maptype-control {
        display: flex;
        flex-direction: row;
        width: auto;
      }

      .gm-style .controls.maptype-control button {
        display: inline-block;
        font-size: 0.5em;
        margin: 0 1px;
        padding: 0 6px;
      }

      .gm-style .controls.maptype-control.maptype-control-is-map .maptype-control-map {
        font-weight: 700;
      }

      .gm-style .controls.maptype-control.maptype-control-is-satellite .maptype-control-satellite {
        font-weight: 700;
      }

      .gm-style .controls.fullscreen-control button {
        display: block;
        font-size: 1em;
        height: 100%;
        width: 100%;
      }

      .gm-style .controls.fullscreen-control .fullscreen-control-icon {
        border-style: solid;
        height: 0.25em;
        position: absolute;
        width: 0.25em;
      }

      .gm-style .controls.fullscreen-control .fullscreen-control-icon.fullscreen-control-top-left {
        border-width: 2px 0 0 2px;
        left: 0.1em;
        top: 0.1em;
      }

      .gm-style .controls.fullscreen-control.is-fullscreen .fullscreen-control-icon.fullscreen-control-top-left {
        border-width: 0 2px 2px 0;
      }

      .gm-style .controls.fullscreen-control .fullscreen-control-icon.fullscreen-control-top-right {
        border-width: 2px 2px 0 0;
        right: 0.1em;
        top: 0.1em;
      }

      .gm-style .controls.fullscreen-control.is-fullscreen .fullscreen-control-icon.fullscreen-control-top-right {
        border-width: 0 0 2px 2px;
      }

      .gm-style .controls.fullscreen-control .fullscreen-control-icon.fullscreen-control-bottom-left {
        border-width: 0 0 2px 2px;
        left: 0.1em;
        bottom: 0.1em;
      }

      .gm-style .controls.fullscreen-control.is-fullscreen .fullscreen-control-icon.fullscreen-control-bottom-left {
        border-width: 2px 2px 0 0;
      }

      .gm-style .controls.fullscreen-control .fullscreen-control-icon.fullscreen-control-bottom-right {
        border-width: 0 2px 2px 0;
        right: 0.1em;
        bottom: 0.1em;
      }

      .gm-style .controls.fullscreen-control.is-fullscreen .fullscreen-control-icon.fullscreen-control-bottom-right {
        border-width: 2px 0 0 2px;
      }

      .forms-map{
        top: 130px;
        left: 10px;
        color: white;
        font-weight: bold;
        z-index: 401;
      }

      .card-detalle{
        width:350px;
        display:none;
        max-height: 850px;
        overflow: none;
        position: absolute;
        right: 10px;
        top: 10px;
      }

      .app-header .navbar-brand {
        width: 200px;
      }

      .sidebar-nav .nav-link span{
        display: none;
      }

      .sidebar-lg-show .app-body .sidebar.sidebar-pills {
        flex: 0 0 80px;
      }

      .sidebar-lg-show .sidebar .sidebar-nav, .sidebar .sidebar-scroll {
          width: 60px;
      }

      .sidebar-lg-show .sidebar .nav {
          width: 60px;
      }

      .sidebar-lg-show .sidebar.sidebar-pills .nav-dropdown.open {
        width: 60px;
      }

      html:not([dir=rtl]) .sidebar {
        margin-left: 0px;
      }

      html:not([dir=rtl]) .sidebar {
        margin-left: 0px;
      }

      .reportCapa{
        display: inline-block;
        margin-left: 5px;        
      }

      .reportCapa img{
        opacity: 0.9;
        cursor: pointer;
      }

      .reportCapa:hover img{
        opacity: 1;
      }

      .exportLasso{
        display: none;
        position:absolute;
        z-index:1;
        top: 251px;
        left: 190px;
      }
    
      .form-check-label {
          color: black;
      }

      #spinnerData{
        background-color: #00000070;
      }
      .cssload-loader-inner {
          bottom: 0;
          height: 58px;
          left: 0;
          margin: auto;
          position: absolute;
          right: 0;
          top: 0;
          width: 97px;
      }

      .cssload-cssload-loader-line-wrap-wrap {
          animation: cssload-spin 2300ms cubic-bezier(.175, .885, .32, 1.275) infinite;
          -o-animation: cssload-spin 2300ms cubic-bezier(.175, .885, .32, 1.275) infinite;
          -ms-animation: cssload-spin 2300ms cubic-bezier(.175, .885, .32, 1.275) infinite;
          -webkit-animation: cssload-spin 2300ms cubic-bezier(.175, .885, .32, 1.275) infinite;
          -moz-animation: cssload-spin 2300ms cubic-bezier(.175, .885, .32, 1.275) infinite;
          box-sizing: border-box;
          -o-box-sizing: border-box;
          -ms-box-sizing: border-box;
          -webkit-box-sizing: border-box;
          -moz-box-sizing: border-box;
          height: 49px;
          left: 0;
          overflow: hidden;
          position: absolute;
          top: 0;
          transform-origin: 50% 100%;
          -o-transform-origin: 50% 100%;
          -ms-transform-origin: 50% 100%;
          -webkit-transform-origin: 50% 100%;
          -moz-transform-origin: 50% 100%;
          width: 97px;
      }

      .cssload-loader-line-wrap {
          border: 4px solid transparent;
          border-radius: 100%;
          -o-border-radius: 100%;
          -ms-border-radius: 100%;
          -webkit-border-radius: 100%;
          -moz-border-radius: 100%;
          box-sizing: border-box;
          -o-box-sizing: border-box;
          -ms-box-sizing: border-box;
          -webkit-box-sizing: border-box;
          -moz-box-sizing: border-box;
          height: 97px;
          left: 0;
          margin: 0 auto;
          position: absolute;
          right: 0;
          top: 0;
          width: 97px;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(1) {
          animation-delay: -57.5ms;
          -o-animation-delay: -57.5ms;
          -ms-animation-delay: -57.5ms;
          -webkit-animation-delay: -57.5ms;
          -moz-animation-delay: -57.5ms;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(2) {
          animation-delay: -115ms;
          -o-animation-delay: -115ms;
          -ms-animation-delay: -115ms;
          -webkit-animation-delay: -115ms;
          -moz-animation-delay: -115ms;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(3) {
          animation-delay: -172.5ms;
          -o-animation-delay: -172.5ms;
          -ms-animation-delay: -172.5ms;
          -webkit-animation-delay: -172.5ms;
          -moz-animation-delay: -172.5ms;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(4) {
          animation-delay: -230ms;
          -o-animation-delay: -230ms;
          -ms-animation-delay: -230ms;
          -webkit-animation-delay: -230ms;
          -moz-animation-delay: -230ms;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(5) {
          animation-delay: -287.5ms;
          -o-animation-delay: -287.5ms;
          -ms-animation-delay: -287.5ms;
          -webkit-animation-delay: -287.5ms;
          -moz-animation-delay: -287.5ms;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(1) .cssload-loader-line-wrap {
          border-color: rgb(234, 71, 71);
          height: 88px;
          width: 88px;
          top: 7px;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(2) .cssload-loader-line-wrap {
          border-color: rgb(234, 71, 71);
          /* border-color: rgb(234, 234, 71); */
          height: 74px;
          width: 74px;
          top: 14px;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(3) .cssload-loader-line-wrap {
          border-color: rgb(234, 71, 71);
          /* border-color: rgb(71, 234, 71); */
          height: 60px;
          width: 60px;
          top: 20px;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(4) .cssload-loader-line-wrap {
          border-color: rgb(234, 71, 71);
          /* border-color: rgb(71, 234, 234); */
          height: 47px;
          width: 47px;
          top: 27px;
      }

      .cssload-cssload-loader-line-wrap-wrap:nth-child(5) .cssload-loader-line-wrap {
          border-color: rgb(234, 71, 71);
          /* border-color: rgb(71, 71, 234); */
          height: 33px;
          width: 33px;
          top: 34px;
      }

      @keyframes cssload-spin {

        0%,
        15% {
          transform: rotate(0);
          transform: rotate(0);
        }

        100% {
          transform: rotate(360deg);
          transform: rotate(360deg)
        }
      }

      @-o-keyframes cssload-spin {

        0%,
        15% {
          -o-transform: rotate(0);
          transform: rotate(0);
        }

        100% {
          -o-transform: rotate(360deg);
          transform: rotate(360deg);
        }
      }

      @-ms-keyframes cssload-spin {

          0%,
          15% {
              -ms-transform: rotate(0);
              transform: rotate(0);
          }

          100% {
              -ms-transform: rotate(360deg);
              transform: rotate(360deg);
          }
      }

      @-webkit-keyframes cssload-spin {

          0%,
          15% {
              -webkit-transform: rotate(0);
              transform: rotate(0);
          }

          100% {
              -webkit-transform: rotate(360deg);
              transform: rotate(360deg);
          }
      }

      @-moz-keyframes cssload-spin {

          0%,
          15% {
              -moz-transform: rotate(0);
              transform: rotate(0);
          }

          100% {
              -moz-transform: rotate(360deg);
              transform: rotate(360deg);
          }
      }
</style>

@endsection

@section('content')

<div id="spinnerData" class="modal" data-backdrop="static" data-keyboard="false">
    <div class="cssload-loader-inner">
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
    </div>
</div>

<div class="app-body">
    <main class="main pt-2">
        <div class="container-fluid animated fadeIn">
        <div class="row">
          <div class="col-sm-2">
            <label for="selectDep">Departamento:</label>
            <select class="form-control" id="selectDep">
              <option value="0">Todos</option>
              <?php 
              /*foreach ($departamentos as $departamento){
                echo "<option value='".$departamento['departamento']."'>".$departamento['departamento']."</option>";
              }*/
              ?>
            </select>
          </div>
          <div class="col-sm-2" id="colProv" style="display: none;">
            <label for="selectProv">Provincia:</label>
            <select class="form-control" id="selectProv" disabled>
              <option value="0">Todos</option>
            </select>
          </div>
          <div class="col-sm-2" id="colDist" style="display: none;">
            <label for="selectDist">Distrito:</label>
            <select class="form-control" id="selectDist" disabled>
              <option value="0">Todos</option>
            </select>
          </div>
          <div id='app' class="col-sm-2">
            <label for="selectFecha">Fecha:</label>
            <v-date-picker :key="datePickerKey" v-model="range" mode="dateTime" :masks="masks" locale="es" is24hr is-range clearable>
              <template v-slot="{ inputValue, inputEvents }">
              <input
                :value="inputValue.start+' - '+inputValue.end"
                v-on="inputEvents.start"
                class="border px-2 py-1 w-32 rounded focus:outline-none focus:border-indigo-300 form-control" style="font-size: 14px;"
                id="fechaMapa"                
              />
              </template>
            </v-date-picker>
          </div>
          <div class="col-sm-2" id="colTipoAlarma">
            <label for="selectTipoAlarma">Tipo de Falla:</label>
            <select class="form-control" id="selectTipoAlarma">
              <option value="2" selected>Todos</option>
              <option value="0">Energía</option>
              <option value="1">Otros</option>
            </select>
          </div>
          <div class="col-sm-2">
            <label for="busqueda" class="form-label">Buscar por Nombre:</label>
            <input type="text" class="form-control" id="nombreMapa" aria-describedby="busqueda por nombre">
          </div>
          <div class="col-sm-2 d-flex">
            <button id="filtrarMapa" type="button" class="btn btn-danger mt-auto">Filtrar</button>          
            <button id="filtrarClearMapa" type="button" class="btn btn-danger mt-auto ml-2">Limpiar Filtros</button>
          </div>  
        </div>
        <button class="btn ruler-map"><i class="la la-ruler-vertical"></i></button>
        <br>
    
    <button type="button" class="btn btn-sm btn-danger exportLasso" onclick="exportarCapa('LASSO')">Exportar</button>
    <!--The div element for the map -->
    <div class="d-flex position-relative">
      <div class="float-start position-absolute forms-map">
      <div class="form-select mb-3">
          <input type="checkbox" id="selectGroup" checked  data-toggle="toggle" data-on="AGRUPADOS" data-off="DESAGRUPADOS" data-onstyle="success" data-offstyle="danger" data-size="sm">
        </div>
        <div class="form-select mb-3">
          <input type="checkbox" id="selectfilter" data-toggle="toggle" data-on="TODO" data-off="ALARMADOS" data-onstyle="success" data-offstyle="danger" data-size="sm">
        </div>
        <div class="form-select mb-3">
          <input type="checkbox" id="selectRelacion" data-toggle="toggle" data-on="RELACIONADOS" data-off="SIN_RELACION" data-onstyle="success" data-offstyle="danger" data-size="sm">
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="dwdm" checked>
          <label class="form-check-label" for="flexCheckDefault">
            DWDM
          </label>
          <div class="reportCapa"><a href="#" onclick="exportarCapa('DWDM')"><img src="{{ asset('images/icono-excel.png') }}" width="25" height="25"></img></a></div>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="rtnSites" checked>
          <label class="form-check-label" for="flexCheckDefault">
            RTN
          </label>
          <div class="reportCapa"><a href="#" onclick="exportarCapa('RTN')"><img src="{{ asset('images/icono-excel.png') }}" width="25" height="25"></img></a></div>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="mbts" checked>
          <label class="form-check-label" for="flexCheckDefault">
            MBTS
          </label>
          <div class="reportCapa"><a href="#" onclick="exportarCapa('MBTS')"><img src="{{ asset('images/icono-excel.png') }}" width="25" height="25"></img></a></div>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="fija" checked>
          <label class="form-check-label" for="flexCheckDefault">
            PLANOS FIJA
          </label>
          <div class="reportCapa"><a href="#" onclick="exportarCapa('PLANOS_FIJA')"><img src="{{ asset('images/icono-excel.png') }}" width="25" height="25"></img></a></div>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="dorsales">
          <label class="form-check-label" for="flexCheckDefault">
            DORSALES
          </label>
        </div>

        <div class="dropdown mt-2"> 
          <button class="btn btn-sm btn-danger dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">ENLACES MW</button> 
          <div class="dropdown-menu" aria-labelledby="dropdownMenuButton"> 
            <form style="padding-left: 0.5rem;"> 
              <div class="form-check check-padre font-weight-normal"> 
                <input class="form-check-input parent" type="checkbox" data-capa="enlaces_mw" data-tipo="polyline" data-filtro="TODO"> 
                <label class="form-check-label" for="enlaces_mw_todos">TODO</label> 
                <?php 
                  /*foreach ($menus_enlaces_mw as $menu){
                    echo "<div class='form-check'>";
                    echo "<input class='form-check-input child' type='checkbox' data-capa='enlaces_mw' data-tipo='polyline' data-filtro='".$menu->vendor."'>";
                    echo "<label class='form-check-label' for='enlaces_mw_".$menu->vendor."'>".$menu->vendor."</label>";
                    echo "</div>";
                  }*/
                ?>
              </div>
            </form> 
          </div> 
        </div>

        <div class="dropdown mt-2"> 
          <button class="btn btn-sm btn-danger dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">ENLACES DORSALES FO</button> 
          <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2"> 
            <form style="padding-left: 0.5rem;"> 
              <div class="form-check check-padre font-weight-normal"> 
                <input class="form-check-input parent" type="checkbox" data-capa="enlaces_dorsales_fo" data-tipo="polylineMulti" data-filtro="TODO" checked> 
                <label class="form-check-label" for="enlaces_dorsales_fo_todos">TODO</label> 
                <?php 
                  /*foreach ($menus_dorsales_fo as $menu){
                    echo "<div class='form-check'>";
                    echo "<input class='form-check-input child' type='checkbox' data-capa='enlaces_dorsales_fo' data-tipo='polylineMulti' data-filtro='".$menu->categoria."' checked>";
                    echo "<label class='form-check-label' for='enlaces_dorsales_".$menu->categoria."'>".$menu->categoria."</label>";
                    echo "</div>";
                  }*/
                ?>
              </div>
            </form> 
          </div> 
        </div>

        <div class="dropdown mt-2"> 
          <button class="btn btn-sm btn-danger dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">ENLACES ENTRE ROUTER</button> 
          <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2"> 
            <form style="padding-left: 0.5rem;">
              <div class="form-check check-padre font-weight-normal"> 
                <input class="form-check-input parent" type="checkbox" data-capa="enlaces_router" data-tipo="polyline" data-filtro="TODO"> 
                <label class="form-check-label" for="enlaces_router_todos">TODO</label> 
                <?php 
                  /*foreach ($menus_enlaces_router as $menu){
                    echo "<div class='form-check'>";
                    echo "<input class='form-check-input child' type='checkbox' data-capa='enlaces_router' data-tipo='polyline' data-filtro='".$menu->tipo."'>";
                    echo "<label class='form-check-label' for='enlaces_router_".$menu->tipo."'>".$menu->tipo."</label>";
                    echo "</div>";
                  }*/
                ?>
              </div>
            </form> 
          </div> 
        </div>
      </div>

      <div class="flex-grow-1"><div id="map"></div></div>
      <div class="card ml-3 card-detalle">        
        <div class="card-header" id="title">          
          <button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="card-body"> 
          <div class="card-fixed">
            <h5></h5>
            <p class="p-head"><p>
            <p class="p-cant1"><p>
            <p class="p-cant2"><p>
            <p class="p-cant3"><p>
            <p class="p-cant4"><p>
            <p class="p-cant5"><p>

              <hr></hr>
              <div>
                <canvas id="myChart"></canvas>
              </div>
              <hr></hr>
            </div>
            <button type="button" onclick="tableToCSV()" class="btn btn-danger" id="csvDownload">Descargar CSV</button>
            <div class="card-scroll" style="overflow: auto;max-height: 300px;">
              <table class="table table-striped" id="tabledata" style="font-size:11px;display:none">
                <thead>
                  <tr>
                    <th scope="col">LOG_SERIAL_NUMBER</th>
                    <th scope="col">ALARM_ID</th>
                    <th scope="col">ALARMNAME</th>
                    <th scope="col">SEVERITY</th>
                    <th scope="col">OCCURRENCETIME</th>
                    <th scope="col">CLEARANCETIME</th>
                    <th scope="col">ALARM_SOURCE</th>
                    <th scope="col">LOCATIONINFORMATION</th>
                    <th scope="col">ESTADO</th>
                  </tr>
                </thead>
              <tbody>
              </tbody>
              </table>
              <table class="table table-striped" id="tabledataTec" style="font-size:11px;display:none">
                <thead>
                <tr>
                    <th scope="col">LOG_SERIAL_NUMBER</th>
                    <th scope="col">ALARM_ID</th>
                    <th scope="col">ALARMNAME</th>
                    <th scope="col">SEVERITY</th>
                    <th scope="col">OCCURRENCETIME</th>
                    <th scope="col">CLEARANCETIME</th>
                    <th scope="col">CELLNAME</th>
                    <!--<th scope="col">ALARM_CAUSE</th>-->
                    <th scope="col">ESTADO</th>
                  </tr>
                </thead>
              <tbody>
              </tbody>
              </table>
              <table class="table table-striped" id="tabledataTec3" style="font-size:11px;display:none">
                <thead>
                <tr>
                    <th scope="col">LOG_SERIAL_NUMBER</th>
                    <th scope="col">ALARM_ID</th>
                    <th scope="col">ALARMNAME</th>
                    <th scope="col">SEVERITY</th>
                    <th scope="col">OCCURRENCETIME</th>
                    <th scope="col">CLEARANCETIME</th>
                    <th scope="col">CELLNAME</th>
                    <th scope="col">CENTRAL</th>
                    <th scope="col">ESTADO</th>
                  </tr>
                </thead>
              <tbody>
              </tbody>
              </table>
              <table class="table table-striped" id="tabledataTec4" style="font-size:11px;display:none">
                <thead>
                <tr>
                    <th scope="col">LOG_SERIAL_NUMBER</th>
                    <th scope="col">ALARM_ID</th>
                    <th scope="col">ALARMNAME</th>
                    <th scope="col">SEVERITY</th>
                    <th scope="col">OCCURRENCETIME</th>
                    <th scope="col">CLEARANCETIME</th>
                    <th scope="col">CELLNAME</th>
                    <!--<th scope="col">SPECIFIC_PROBLEM</th>-->
                    <th scope="col">ESTADO</th>
                  </tr>
                </thead>
              <tbody>
              </tbody>
              </table>
              <table class="table table-striped" id="tabledataMbts" style="font-size:11px;display:none">
                <thead>
                <tr>
                    <th scope="col">LOG_SERIAL_NUMBER</th>
                    <th scope="col">ALARM_ID</th>
                    <th scope="col">ALARMNAME</th>
                    <th scope="col">SEVERITY</th>
                    <th scope="col">OCCURRENCETIME</th>
                    <th scope="col">CLEARANCETIME</th>
                    <th scope="col">ACKNOWLEDGEMENTTIME</th>
                    <th scope="col">CELLNAME</th>
                    <th scope="col">MBTS</th>
                    <th scope="col">ESTADO</th>
                  </tr>
                </thead>
              <tbody>
              </tbody>
              </table>
              <table class="table table-striped" id="tabledataFija" style="font-size:11px;display:none">
                <thead>
                <tr>
                    <th scope="col">ID_INCIDENCIA</th>
                    <th scope="col">PRIORIDAD</th>
                    <th scope="col">FECHA_CREACION_INCIDENTE</th>
                    <th scope="col">FECHA_CIERRE_INCIDENTE</th>
                    <th scope="col">PLANO</th>
                    <th scope="col">SERVICIO</th>
                    <th scope="col">ELEMENTO_RED_AFECTADO</th>
                    <!--<th scope="col">ALARM_CAUSE</th>-->
                    <th scope="col">ESTADO</th>
                  </tr>
                </thead>
              <tbody>
              </tbody>
              </table>
            <div>
        </div>
      </div>
    </div>

    <!-- Hide controls until they are moved into the map. -->
    <div style="display: none">
      <div class="controls zoom-control">
        <button class="zoom-control-in" title="Zoom In">+</button>
        <button class="zoom-control-out" title="Zoom Out">−</button>
      </div>
      <div class="controls maptype-control maptype-control-is-map">
        <button class="maptype-control-map" title="Show road map">Map</button>
        <button
          class="maptype-control-satellite"
          title="Show satellite imagery"
        >
          Satellite
        </button>
      </div>
      <div class="controls fullscreen-control">
        <button title="Toggle Fullscreen">
          <div
            class="fullscreen-control-icon fullscreen-control-top-left"
          ></div>
          <div
            class="fullscreen-control-icon fullscreen-control-top-right"
          ></div>
          <div
            class="fullscreen-control-icon fullscreen-control-bottom-left"
          ></div>
          <div
            class="fullscreen-control-icon fullscreen-control-bottom-right"
          ></div>
        </button>
      </div>
    </div>
</div>

</main>

</div>

@endsection

@section('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js" integrity="sha256-xLD7nhI62fcsEZK2/v8LsBcb4lG7dgULkuXoXB/j91c=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
<script src="https://unpkg.com/leaflet-lasso@2.2.12/dist/leaflet-lasso.umd.min.js"></script>
<!-- 1. Link Vue Javascript -->
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.js"></script>
<!-- 2. Link VCalendar Javascript (Plugin automatically installed) -->
<script src='https://unpkg.com/v-calendar@2.4.1/lib/v-calendar.umd.min.js'></script>
<!--3. Create the Vue instance-->
<script>
  var dias_3 = new Date();
  var fechaVue = new Vue({
    el: '#app',
    data: {
      datePickerKey: 0,
      range:{
        start: new Date(),
        end: dias_3.setDate(dias_3.getDate() - 3)
      },   
      masks: {
        input: 'DD/MM/YYYY HH:mm',
      },
    },
    methods: {
      actualizarFecha(){
        dias_3 = new Date();
        this.range.start = new Date();
        this.range.end = dias_3.setDate(dias_3.getDate() - 3);
        this.datePickerKey++;
      }
    }
  });
</script>
<script>
    var myLineChart;
    var tipoCsv,fechaCsv;
    var fillColorBk,opt,clear=0,map,icons,marker,oms,loadedsites4g=0,loadedsites3g=0;
    var url = "http://172.17.27.157/portalmonitoreo/assets/map/map_19_6748.json";
    var url_dorsales = "http://172.17.27.157/dorsales.geojson";
    var url_dorsales_fo = "http://172.17.27.157/portalmonitoreonoc/map/mapa2/rutas.json"
    var ubidep=[],ubiprov= [],ubidist= [],ubiccpp= [],ccpp= [],polyccpp = [],markers = [],sites = [],feature = [],listPolyBase=[];
    var slcubg = "555";
    let infoWindow;
    var loadRtn = 0;
    var loadRtnAlarm = 0;
    var loadMbts = 0;
    var loadMbtsAlarm = 0;
    var loadDwdm = 0;
    var loadDwdmAlarm = 0;
    var sync = 0;
    var listOfPolygons = [];
    var measureTool;
    var ruler_event = 0;
    var ruler = 0;
    var temp_lat = 0;
    var temp_lng = 0;
    var temp_marker;
    var pruneCluster = new PruneClusterForLeaflet();
    pi2 = Math.PI * 2;
    var dorsales_boundary =[];
    var polyline_boundary = [];
    var fija_boundary = [];
    var fija_marcadores = [];
    var layers_selected = 0;
    var lassoControl;
    var id_lasso = [];
    //var lasso;

    $(document).ready(function () {

      //$("#spinnerData").show();

      $("#selectDep").change(function () {
        changeDpto();
      });

      $("#selectProv").change(function () {
        changeProv();
      });

      $("#selectDist").change(function () {
        reset();
      });

      $(".close").click(function() {
        $(".card").hide();
      });

      $("#filtrarMapa").click(function() {
        reloadMap();
      });

      $("#filtrarClearMapa").click(function() {
        clearInputs();
        reloadMap();
      });

      $('#toggle-button').click(function() {
          if($('#panel').hasClass('show-panel')) {
              $('#panel').removeClass('show-panel').addClass('hide-panel');
              $(this).text('Show');
          }
          else {
              $('#panel').removeClass('hide-panel').addClass('show-panel');
              $(this).text('Hide');
          }
      });

      $('.ruler-map').click(function() {
          if($('.ruler-map').hasClass('active')) {
              $('.ruler-map').removeClass('active');
              ruler = 0;
              ruler_event = 0;
              measureTool.end();
          }
          else {
              $('.ruler-map').addClass('active');
              ruler = 1;
          }
      });
      
      $("#selectGroup").on( 'change', function() {  
        if(!$(this).is(':checked')) {      
          pruneCluster.Cluster.Size = 0.001;
          pruneCluster.ProcessView();
        }else{
          pruneCluster.Cluster.Size = 50;
          pruneCluster.ProcessView();
        }
      });

      $("#selectfilter").on( 'change', function() {
        if(!$(this).is(':checked')) {  
          $('#selectGroup').bootstrapToggle('enable')
        }else{
          $('#selectGroup').bootstrapToggle('on')
          $('#selectGroup').bootstrapToggle('disable')
          pruneCluster.Cluster.Size = 50;
          pruneCluster.ProcessView();
        }
        reset();
      });

      $("#selectRelacion").on( 'change', function() {
        reset();
      });     

      $("#selectTipoAlarma").on( 'change', function() {
        reset();
      });

      $("#dwdm").on( 'change', function() {
        if( $(this).is(':checked') ) {
            // Hacer algo si el checkbox ha sido seleccionado
            loadDwdmMarkers($("#fechaMapa").val(),$("#nombreMapa").val());
        } else {
            // Hacer algo si el checkbox ha sido deseleccionado
            clearMarkersSites('dwdm');
            clearMarkersSites('alarma');
            clearMarkersSites('dwdm_r');
            clearMarkersSites('alarma_r');
        }
      });

      $("#rtnSites").on( 'change', function() {
        if( $(this).is(':checked')){
          // Hacer algo si el checkbox ha sido seleccionado
          loadRtnMarkers($("#fechaMapa").val(),$("#nombreMapa").val());

        }else {
          // Hacer algo si el checkbox ha sido deseleccionado
          clearMarkersSites('sites');
          clearMarkersSites('nodal');
          clearMarkersSites('rtn_alarmas');    
          clearMarkersSites('sites_r');
          clearMarkersSites('nodal_r');
          clearMarkersSites('rtn_alarmas_r');        
        }
      });

      $("#mbts").on( 'change', function() {
        if( $(this).is(':checked') ) {
            // Hacer algo si el checkbox ha sido seleccionado
            loadMbtsMarkers($("#fechaMapa").val(),$("#nombreMapa").val());
        } else {
            // Hacer algo si el checkbox ha sido deseleccionado
            clearMarkersSites('mbts_energia');
            clearMarkersSites('mbts_activas');
            clearMarkersSites('mbts');
            clearMarkersSites('mbts_energia_r');
            clearMarkersSites('mbts_activas_r');
            clearMarkersSites('mbts_r');
        }
      });

      $("#fija").on( 'change', function() {
        if( $(this).is(':checked') ) {
            // Hacer algo si el checkbox ha sido seleccionado
            fillColorPlano($("#fechaMapa").val(),$("#nombreMapa").val());
        } else {
            // Hacer algo si el checkbox ha sido deseleccionado
            /* map.data.forEach(function (feature) {
              if(feature.getProperty('tipo') == 'fija'){
                map.data.remove(feature);
              }              
            }); */
            if(fija_boundary.length>0){
              fija_boundary.forEach(planos => {
                planos.remove();
              });
            }
            if(fija_marcadores.length>0){
              fija_marcadores.forEach(marcadores => {
                marcadores.remove();
              });
            }
            
            //fija_boundary.remove();
            //clearMarkersSites('fija');
        }
      });

      $("#dorsales").on( 'change', function() {
        if( $(this).is(':checked') ) {
            // Hacer algo si el checkbox ha sido seleccionado
            $("#spinnerData").show();
            loadCapa("dorsales","geoJson","TODO",$("#fechaMapa").val(),$("#nombreMapa").val());
            //planosDorsales();
        } else {
            // Hacer algo si el checkbox ha sido deseleccionado
            /* map.data.forEach(function (feature) {
              if(feature.getProperty('tipo') == 'dorsal'){
                map.data.remove(feature);
              }
            }); */
            if(polyline_boundary.length>0){
              polyline_boundary.forEach(dorsales => {
                if(dorsales.options.capa == $(this).data('capa')){
                  dorsales.remove();
                }
              });
            }
            //clearMarkersSites('dorsales');
        }
      });

      $('.check-padre .parent').click(function() { 
        $(this).parent().find('.child').prop('checked', this.checked);
        if( $(this).is(':checked') ) {
            // Hacer algo si el checkbox ha sido seleccionado
            $("#spinnerData").show();
            loadCapa($(this).data('capa'),$(this).data('tipo'),$(this).data('filtro'),$("#fechaMapa").val(),$("#nombreMapa").val());
        }else {
            // Hacer algo si el checkbox ha sido deseleccionado
            /* map.data.forEach(function (feature) {
              if(feature.getProperty('tipo') == 'dorsal'){
                map.data.remove(feature);
              }
            }); */
            if(polyline_boundary.length>0){
              polyline_boundary.forEach(dorsales => {
                if(dorsales.options.capa == $(this).data('capa')){
                  dorsales.remove();
                }
              });
            }
            //clearMarkersSites('dorsalesFo');
        }        
      });

      $('.check-padre .child').change(function() { 
        var checkedChild = $(this).parent().parent().find('.child:checked').length;
        var totalChild = $(this).parent().parent().find('.child').length;
        $(this).parent().parent().find('.parent').prop('checked', checkedChild === totalChild); 
        if( $(this).is(':checked') ) {
            // Hacer algo si el checkbox ha sido seleccionado
            $("#spinnerData").show();
            loadCapa($(this).data('capa'),$(this).data('tipo'),$(this).data('filtro'),$("#fechaMapa").val(),$("#nombreMapa").val());
        }else{
            // Hacer algo si el checkbox ha sido deseleccionado
            /* map.data.forEach(function (feature) {
              if(feature.getProperty('tipo') == 'dorsal'){
                map.data.remove(feature);
              }
            }); */
            if(polyline_boundary.length>0){
              polyline_boundary.forEach(dorsales => {
                if(dorsales.options.capa == $(this).data('capa') && dorsales.options.filtro == $(this).data('filtro')){
                  dorsales.remove();
                }
              });
            }
            //clearMarkersSites('dorsalesFo');
        }
      });

      const ctx = document.getElementById('myChart');

      myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Dataset 1',
            data: [],
            borderColor: "red",
            yAxisID: 'y',
          },
          {
            label: 'Dataset 2',
            data: [],
            borderColor: "blue",
            yAxisID: 'y',
          },
          {
            label: 'Dataset 3',
            data: [],
            borderColor: "green2",
            yAxisID: 'y',
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });

      $(document).keydown(function(e){
        if(e.keyCode === 27){
          lassoControl.disable();
          $(".exportLasso").hide();
          if( layers_selected==1){
            layers_selected = 0;
            resetSelectedState();
          }          
        }
      });

      setInterval(function(){
        $.ajax({
          url: '/portalmonitoreonoc/verificar-sesion',
          method: 'GET',
          success: function(response){
            if(response.authenticated){
              refrescarMapa();
            }else{
              window.location.href="/portalmonitoreonoc/login";
            }            
          },
          error: function(xhr, status, error){
            window.location.href="/portalmonitoreonoc/login";
          }
        });

      }, 300000);

    });

    function refrescarMapa(){
      sync = 1;
      fechaVue.actualizarFecha();
      reset();
    }

    function setSelectedLayers(layers) {

      id_lasso = [];

      layers_selected = 1;

      map.eachLayer(layer => {
          if (layer instanceof L.Marker) {
              //layer.setIcon(new L.Icon.Default());
              layer.setOpacity(0.2);
          } else if (layer instanceof L.Path) {
              //layer.setStyle({ color: '#3388ff' });
          }
      });

      layers.forEach(layer => {
        
          if(layer instanceof L.Marker){

            if(layer._population != 1 && typeof layer._population !== "undefined"){
              var markers_temp = pruneCluster.Cluster.FindMarkersInArea(layer._leafletClusterBounds);
              markers_temp.forEach( marker =>{
                //pruneCluster.RemoveMarkers(markers);
                id_lasso.push(marker.data.id);        
                //pruneCluster.ProcessView();
                //clearMarkersId(marker.data.id);
                //marker.setOpacity(0.2);
              }); 
            }else{
              id_lasso.push(layer.options.icon.options.id);
            }              
            layer.setOpacity(1);
          }else if (layer instanceof L.Path) {
              //layer.setStyle({ color: '#ff4620' });
          }
      });

      $(".exportLasso").show();

      //layers.length ? `Selected ${layers.length} layers` : '';
    }

    function resetSelectedState() {
      
      map.eachLayer(layer => {
          if (layer instanceof L.Marker) {
              //layer.setIcon(new L.Icon.Default());
              layer.setOpacity(1);
          } else if (layer instanceof L.Path) {
              //layer.setStyle({ color: '#3388ff' });
          }
      });

      //lassoResult.innerHTML = '';
    }

    function loadRtnMarkers(fecha = "", nombre = ""){    
      var relacion = document.getElementById('selectRelacion').checked;
      if(document.getElementById('selectfilter').checked){
        if(loadRtn && loadRtnAlarm){
          if(relacion){
            setMapOnSites(map,'rtn_alarmas_r');
            setMapOnSites(map,'nodal_r');
          }else{
            setMapOnSites(map,'rtn_alarmas');
            setMapOnSites(map,'nodal');
          }
        }else{
          loadRtn = 1;
          loadRtnAlarm = 1;
          $.ajax({
              url: "/api_data.php",
              type: 'GET',
              //dataType: 'json',
              data: "id=todo&type=rtnAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
              beforeSend:function () {
                $("#spinnerData").show();
              },
              success: function(data) {
                
                data = JSON.parse(data);
                const features2 = data[0];

                // Create markers.
                for (let i = 0; i < features2.length; i++) {
                  if(features2[i].relacionado == "1"){
                    addMarkerCVM(features2[i],icons['rtn'+features2[i].estado].icon,"rtn_alarmas_r");
                  }
                  if(!relacion){
                    addMarkerCVM(features2[i],icons['rtn'+features2[i].estado].icon,"rtn_alarmas");
                  }                  
                }

                if(!loadRtnAlarm){

                  const features3 = data[1];

                  // Create markers.
                  for (let i = 0; i < features3.length; i++) {
                    if(features3[i].relacionado == "1"){
                      addMarkerCVM(features3[i],icons['nodal'/*features[i].type*/].icon,"nodal_r");
                    }
                    if(!relacion){
                      addMarkerCVM(features3[i],icons['nodal'/*features[i].type*/].icon,"nodal");
                    }                  
                  }
                }

                if(relacion){
                  setMapOnSites(map,'rtn_alarmas_r');
                  setMapOnSites(map,'nodal_r');
                }else{
                  setMapOnSites(map,'rtn_alarmas');
                  setMapOnSites(map,'nodal');
                }

              },
              complete: function () {
                $("#spinnerData").hide();
              },
              error: function(data){
                new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                $("#spinnerData").hide();
                console.log(data);
              }
          });
        } 
        }else{

        if(loadRtnAlarm){
          if(relacion){
            setMapOnSites(map,'rtn_alarmas_r');
          }else{
            setMapOnSites(map,'rtn_alarmas');
          }
        }else{
          loadRtnAlarm = 1;
          $.ajax({
            url: "/api_data.php",
            type: 'GET',
            //dataType: 'json',
            data: "id=alarmado&type=rtnAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
            beforeSend:function () {
              $("#spinnerData").show();
            },
            success: function(data) {
              data = JSON.parse(data);
              const features9 = data[0];

              // Create markers.
              for (let i = 0; i < features9.length; i++) {
                if(features9[i].relacionado == "1"){
                  addMarkerCVM(features9[i],icons['rtn'+features9[i].estado].icon,"rtn_alarmas_r");
                }
                if(!relacion){
                  addMarkerCVM(features9[i],icons['rtn'+features9[i].estado].icon,"rtn_alarmas");
                }
              }

              if(relacion){
                setMapOnSites(map,'rtn_alarmas_r');
              }else{
                setMapOnSites(map,'rtn_alarmas');
              }
            },
            complete: function () {
              $("#spinnerData").hide();
            },
            error: function(data){
              new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
              $("#spinnerData").hide();
              console.log(data);
            }
          });            
        }
      }
    }

    function loadMbtsMarkers(fecha = "", nombre = ""){
      var relacion = document.getElementById('selectRelacion').checked;
      if(document.getElementById('selectfilter').checked){
        if(loadMbts && loadMbtsAlarm){
          if(relacion){
            setMapOnSites(map,'mbts_r');
            setMapOnSites(map,'mbts_activas_r');
            setMapOnSites(map,'mbts_energia_r');
          }else{
            setMapOnSites(map,'mbts');
            setMapOnSites(map,'mbts_activas');
            setMapOnSites(map,'mbts_energia');
          }            
        }else{
          loadMbtsAlarm =1;
          loadMbts = 1;
          $.ajax({
              url: "/api_data.php",
              type: 'GET',
              //dataType: 'json',
              data: "id=todo&type=mbtsAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
              beforeSend:function () {
                $("#spinnerData").show();
              },
              success: function(data) {
                data = JSON.parse(data);

                features = data[1];
                // Create markers.
                for (let i = 0; i < features.length; i++) {
                    if(features[i].estado != 3 && features[i].estado != 4){
                      if(features[i].relacionado == "1"){
                        addMarkerCVM(features[i],icons['tec'].icon,"mbts_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features[i],icons['tec'].icon,"mbts");
                      }                        
                    }                   
                }

                if(!loadMbtsAlarm){
                
                  features2 = data[0];

                  // Create markers.
                  for (let i = 0; i < features2.length; i++) {
                    if(features2[i].relacionado == "1"){
                      addMarkerCVM(features2[i],icons['tec'+features2[i].estado].icon,"mbts_activas_r");
                    }
                    if(!relacion){
                      addMarkerCVM(features2[i],icons['tec'+features2[i].estado].icon,"mbts_activas");
                    }
                    if(features2[i].estado == 4){
                      if(features2[i].relacionado == "1"){
                        addMarkerCVM(features2[i],icons['tec'+features2[i].estado].icon,"mbts_energia_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features2[i],icons['tec'+features2[i].estado].icon,"mbts_energia");
                      }
                    } 
                  }
                }

                //clearMarkersSites('mbts');
                if(relacion){
                  setMapOnSites(map,'mbts_r');
                  setMapOnSites(map,'mbts_activas_r');
                  setMapOnSites(map,'mbts_energia_r');
                }else{
                  setMapOnSites(map,'mbts');
                  setMapOnSites(map,'mbts_activas');
                  setMapOnSites(map,'mbts_energia');
                } 
              },
              complete: function () {
                $("#spinnerData").hide();
              },
              error: function(data){
                new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                $("#spinnerData").hide();
                console.log(data);
              }
          });
        } 
      }else{

        if( $("#selectTipoAlarma").val() == 0){
          if(loadMbtsAlarm){
            if(relacion){
              setMapOnSites(map,'mbts_energia_r');
            }else{
              setMapOnSites(map,'mbts_energia');
            }
          }else{
            loadMbtsAlarm = 1;
            $.ajax({
              url: "/api_data.php",
              type: 'GET',
              //dataType: 'json',
              data: "id=alarmado&type=mbtsAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
              beforeSend:function () {
                $("#spinnerData").show();
              },
              success: function(data) {
                data = JSON.parse(data);
                const features = data[0];

                // Create markers.
                for (let i = 0; i < features.length; i++) {
                  if(features[i].estado == 4){
                    if(features[i].relacionado == "1"){
                      addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_energia_r");
                    }
                    if(!relacion){
                      addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_energia");
                    }                      
                  }
                }

                //clearMarkersSites('mbts_activas');
                if(relacion){
                  setMapOnSites(map,'mbts_energia_r');
                }else{
                  setMapOnSites(map,'mbts_energia');
                }
              },
              complete: function () {
                $("#spinnerData").hide();
              },
              error: function(data){
                new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                $("#spinnerData").hide();
                console.log(data);
              }
            });            
          }
        }else{
          if( $("#selectTipoAlarma").val() == 2){
            if(loadMbtsAlarm){
              if(relacion){
                setMapOnSites(map,'mbts_activas_r');
                setMapOnSites(map,'mbts_energia_r');
              }else{
                setMapOnSites(map,'mbts_activas');
                setMapOnSites(map,'mbts_energia');
              }
            }else{
              loadMbtsAlarm = 1;
              $.ajax({
                url: "/api_data.php",
                type: 'GET',
                //dataType: 'json',
                data: "id=alarmado&type=mbtsAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
                beforeSend:function () {
                  $("#spinnerData").show();
                },
                success: function(data) {
                  data = JSON.parse(data);
                  const features = data[0];

                  // Create markers.
                  for (let i = 0; i < features.length; i++) {
                    if(features[i].estado == 3){
                      if(features[i].relacionado == "1"){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_activas_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_activas");
                      }
                    }
                    if(features[i].estado == 4){
                      if(features[i].relacionado == "1"){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_energia_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_energia");
                      }                      
                    }
                  }

                  //clearMarkersSites('mbts_activas');
                  if(relacion){
                    setMapOnSites(map,'mbts_activas_r');
                    setMapOnSites(map,'mbts_energia_r');
                  }else{
                    setMapOnSites(map,'mbts_activas');
                    setMapOnSites(map,'mbts_energia');
                  } 
                },
                complete: function () {
                  $("#spinnerData").hide();
                },
                error: function(data){
                  new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                  $("#spinnerData").hide();
                  console.log(data);
                }
              });            
            }
          }else{

            if(loadMbtsAlarm){
              if(relacion){
                setMapOnSites(map,'mbts_activas_r');
              }else{
                setMapOnSites(map,'mbts_activas');
              } 
            }else{
              loadMbtsAlarm = 1;
              $.ajax({
                url: "/api_data.php",
                type: 'GET',
                //dataType: 'json',
                data: "id=alarmado&type=mbtsAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
                beforeSend:function () {
                  $("#spinnerData").show();
                },
                success: function(data) {
                  data = JSON.parse(data);
                  const features = data[0];

                  // Create markers.
                  for (let i = 0; i < features.length; i++) {
                    if(features[i].estado == 3){
                      if(features[i].relacionado == "1"){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_activas_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_activas");
                      }                      
                    }
                    if(features[i].estado == 4){
                      if(features[i].relacionado == "1"){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_energia_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features[i],icons['tec'+features[i].estado].icon,"mbts_energia");
                      }                      
                    }
                  }

                  if(relacion){
                    setMapOnSites(map,'mbts_activas_r');
                  }else{
                    setMapOnSites(map,'mbts_activas');
                  }
                },
                complete: function () {
                  $("#spinnerData").hide();
                },
                error: function(data){
                  new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                  $("#spinnerData").hide();
                  console.log(data);
                }
              });            
            }
          }
        }
      }
    }

    function loadDwdmMarkers(fecha = "", nombre = ""){
      var relacion = document.getElementById('selectRelacion').checked;
      if(document.getElementById('selectfilter').checked){
          if(loadDwdm && loadDwdmAlarm){
            if(relacion){
              setMapOnSites(map,'dwdm_r');
              setMapOnSites(map,'alarma_r');
            }else{
              setMapOnSites(map,'dwdm');
              setMapOnSites(map,'alarma');
            }            
          }else{
            loadDwdmAlarm = 1;
            loadDwdm = 1;
            $.ajax({
                url: "/api_data.php",
                type: 'GET',
                //dataType: 'json',
                data: "id=todo&type=dwdmAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
                beforeSend:function () {
                  $("#spinnerData").show();
                },
                success: function(data) {
                  data = JSON.parse(data);
                  features = data[1];
                  // Create markers.
                  for (let i = 0; i < features.length; i++) {
                    if(features[i].relacionado == "1"){
                      addMarkerCVM(features[i],icons['dwdm'].icon,"dwdm_r");
                    }
                    if(!relacion){
                      addMarkerCVM(features[i],icons['dwdm'].icon,"dwdm");
                    }
                  }

                  if(!loadDwdmAlarm){
                    features2 = data[0];
                    // Create markers.
                    for (let i = 0; i < features2.length; i++) {
                      if(features2[i].relacionado == "1"){
                        addMarkerCVM(features2[i],icons['alarma'+features2[i].estado].icon,"alarma_r");
                      }
                      if(!relacion){
                        addMarkerCVM(features2[i],icons['alarma'+features2[i].estado].icon,"alarma");
                      }                    
                    }
                  }  

                  if(relacion){
                    setMapOnSites(map,'dwdm_r');
                    setMapOnSites(map,'alarma_r');
                  }else{
                    setMapOnSites(map,'dwdm');
                    setMapOnSites(map,'alarma');
                  }  

                },
                complete: function () {
                  $("#spinnerData").hide();
                },
                error: function(data){
                  new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                  $("#spinnerData").hide();
                  console.log(data);
                }
            });
          }
        }else{
            if(loadDwdmAlarm){
              if(relacion){
                setMapOnSites(map,'alarma_r');
              }else{
                setMapOnSites(map,'alarma');
              }                
            }else{
              loadDwdmAlarm = 1;
              $.ajax({
                url: "/api_data.php",
                type: 'GET',
                //dataType: 'json',
                data: "id=alarmado&type=dwdmAll&fecha="+fecha+"&nombre="+nombre+"&relacionado="+relacion,
                beforeSend:function () {
                  $("#spinnerData").show();
                },
                success: function(data) {
                  //console.log(data);
                  data = JSON.parse(data);
                  const features = data[0];

                  // Create markers.
                  for (let i = 0; i < features.length; i++) {
                    if(features[i].relacionado == "1"){
                      addMarkerCVM(features[i],icons['alarma'+features[i].estado].icon,"alarma_r");
                    }
                    if(!relacion){
                      addMarkerCVM(features[i],icons['alarma'+features[i].estado].icon,"alarma");
                    }
                  }

                  if(relacion){
                    setMapOnSites(map,'alarm_r');
                  }else{
                    setMapOnSites(map,'alarma');
                  }
                },
                complete: function () {
                  $("#spinnerData").hide();
                },
                error: function(data){
                  new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                  $("#spinnerData").hide();
                  console.log(data);
                }
              });            
            }
        }
    }

    function fillColorPlano(fecha = "", nombre = ""){
      selectDep = $("#selectDep").val();
      selectProv = $("#selectProv").val();
      selectDist = $("#selectDist").val();
      $.ajax({
        url: "/api_data.php",
        type: 'GET',
        //dataType: 'json',
        data: "id=alarmado&type=fijaAll&fecha="+fecha+"&nombre="+nombre,
        beforeSend:function () {
          $("#spinnerData").show();
        },
        success: function(data) {
          data = JSON.parse(data);
          jsonfija = data[0];

          jsonfija.forEach(jn => {
            
            if(selectDep != "0"){
              if(selectProv=="0"){
                if(jn.dep != selectDep){
                  return;
                }
              }else{
                if(selectDist=="0"){
                  if(jn.dep != selectDep || jn.prov != selectProv){
                    return;
                  }
                }else{
                  if(jn.dep != selectDep || jn.prov != selectProv || jn.dist != selectDist){
                    return;
                  }
                }
              }
            }

            if($("#nombreMapa").val() != "" && sync == 1){
              texto = jn.id;
              texto = texto.toLowerCase();
              if(texto.indexOf($("#nombreMapa").val().toLowerCase()) < 0){
                return;
              }
            }
            
            if(document.getElementById('selectfilter').checked && jn.estado == 1){

              style = {
                fillColor: '#008000',
                //fillOpacity: 1,
                fillOpacity: 0.6,
                color: '#008000',
                //strokeWeight: 3,
                weight: 0,
                strokeWeight: 1
              };

              polilinea = new L.geoJson(jn.coordenadas).addTo(map).on('click', function(event) {
                param = jn.id;
                viewDataMap(param);
              });

              polilinea.setStyle(style);
              polilinea.setStyle({filtro: jn.id});

              fija_boundary.push(polilinea);

            }else{
              if(jn.estado == 0){

                style = {                         
                  fillColor: '#FF0000',
                  //fillOpacity: 1,
                  fillOpacity: 0.6,
                  color: '#FF0000',
                  weight: 2,
                  //strokeWeight: 3,
                  strokeWeight: 1
                };

                polilinea = new L.geoJson(jn.coordenadas).addTo(map).on('click', function(event) {
                  param = jn.id;
                  viewDataMap(param);
                });

                polilinea.setStyle(style);
                polilinea.setStyle({filtro: jn.id});

                fija_boundary.push(polilinea);

                var myIcon = L.icon({
                  iconUrl: 'https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi3.png',
                  id: jn.plano
                });

                path = jn.coordenadas.coordinates[0][0];
                fija_marcadores.push(L.marker([parseFloat(path[1]), parseFloat(path[0])],{icon: myIcon}).addTo(map).on('click', function(e) {
                    map.flyTo(e.latlng, 14)
                }));

              }
            }          
          });
          $("#spinnerData").hide();
        },
        complete: function () {
          
        },
        error: function(data){
          new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
          $("#spinnerData").hide();
          console.log(data);
        }
      });  
      
    }

    function planosDorsales(){
      /*map.data.loadGeoJson(url_dorsales,{},function(features){
        features.forEach(ft => {
          ft.setProperty('tipo', 'dorsal');
        });
        
        $("#spinnerData").hide();
      });*/

      $.ajax({
        dataType: "json",
        url: url_dorsales,
        success: function(data) {
            $(data.features).each(function(key, data) {

              dorsales_boundary.push(new L.geoJson(data));

              style = {
                color: '#000000',
              };              
              
              dorsales_boundary[key].setStyle(function (feature) {
                return style;
              });

              dorsales_boundary[key].addTo(map);
            });
            $("#spinnerData").hide();
        },
        error: function(data){
          new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
          $("#spinnerData").hide();
          console.log(data);
        }
      });
    }

    function loadCapa(capa,tipo,filtro,fecha = "", nombre = ""){
      $.ajax({
          url: "/api_data.php",
          type: 'GET',
          //dataType: 'json',
          data: "id="+filtro+"&type="+capa+"&fecha="+fecha+"&nombre="+nombre,
          beforeSend:function () {
            $("#spinnerData").show();
          },
          success: function(data) {
            data = JSON.parse(data);
            var polilinea = "";
            var coor = "";

            $(data[0]).each(function(key, data) {
              // create a red polyline from an array of LatLng points

              var latlngs = [];

              if(tipo=="polyline" && (filtro == data.opcion || filtro == "TODO")){
                latlngs = [
                  [parseFloat(data.lat_origen),parseFloat(data.lng_origen)],
                  [parseFloat(data.lat_destino),parseFloat(data.lng_destino)],                  
                ];

                polilinea = L.polyline(latlngs, {color: data.color}).addTo(map);
                polilinea.setStyle({capa: capa});
                polilinea.setStyle({filtro: data.opcion});

                if(capa == 'enlaces_mw'){
                  var popupContent = `<h5 class="text-danger">Detalle de Enlace</h5><table class="table table-sm">
                              <tbody><tr>
                                <th class="mat-header-cell" style="width:85px!important;">Origen</th>
                                <td class="mat-cell">`+data.nombre_origen+`</td>
                              </tr>
                              <tr>
                                <th class="mat-header-cell">Destino</th>
                                <td class="mat-cell">`+data.nombre_destino+`</td>
                              </tr>
                              <tr>
                                <th class="mat-header-cell">Configuracion</th>
                                <td class="mat-cell">`+data.configuracion+`</td>
                              </tr>
                              <tr>
                                <th class="mat-header-cell">Capacidad</th>
                                <td class="mat-cell">`+data.capacidad+`</td>
                              </tr>
                              <tr>
                                <th class="mat-header-cell">Marca</th>
                                <td class="mat-cell">`+data.opcion+`</td>
                              </tr>
                              <tr>
                                <th class="mat-header-cell">Estado</th>
                                <td class="mat-cell">`+data.estado+`</td>
                              </tr>
                            </tbody></table>`;
                }

                if(capa == 'enlaces_router'){
                  var popupContent = `<h5 class="text-danger">Detalle de Enlace</h5><table class="table table-sm">
                            <tbody><tr>
                              <th class="mat-header-cell style="width:85px!important;">Tipo enlace</th>
                              <td class="mat-cell">`+data.opcion+`</td>
                            </tr>
                            <tr>
                              <th class="mat-header-cell">Origen - Puerto</th>
                              <td class="mat-cell">`+data.port_origen+`</td>
                            </tr>
                            <tr>
                              <th class="mat-header-cell">Destino - Puerto</th>
                              <td class="mat-cell">`+data.port_destino+`</td>
                            </tr>
                            <tr>
                              <th class="mat-header-cell">Capacidad</th>
                              <td class="mat-cell">`+data.capacidad+` Gbps</td>
                            </tr>
                            <tr>
                              <th class="mat-header-cell">Transporte</th>
                              <td class="mat-cell">`+data.transp+`</td>
                            </tr>
                            <tr>
                              <th class="mat-header-cell">Categoria</th>
                              <td class="mat-cell">`+data.categoria+`</td>
                            </tr>
                          </tbody></table>`;
                }

                polilinea.bindPopup(popupContent);

                polyline_boundary.push(polilinea);
              }

              if(tipo=="polylineMulti" && (filtro == data.opcion || filtro == "TODO")){

                if(data.coordenadas != null){

                  var latlngs = [];

                  $(data.coordenadas.split(',0')).each(function(key, data) {

                    if(data != ""){
                      coor = data.split(',');
                      latlngs.push([parseFloat($.trim(coor[1])),parseFloat($.trim(coor[0]))]);
                    }

                  });

                  polilinea = L.polyline(latlngs, {color: data.color}).addTo(map);
                  polilinea.setStyle({capa: capa});
                  polilinea.setStyle({filtro: data.opcion});

                  if(capa == 'enlaces_dorsales_fo'){
                    var popupContent = `<h5 class="text-danger">Detalle de Enlace</h5><table class="table table-sm">
                                <tbody><tr>
                                  <th class="mat-header-cell" style="width:85px!important;">Nombre</th>
                                  <td class="mat-cell">`+data.titulo+`</td>
                                </tr>
                                <tr>
                                  <th class="mat-header-cell">Categoria</th>
                                  <td class="mat-cell">`+data.opcion+`</td>
                                </tr>
                              </tbody></table>`;
                }

                polilinea.bindPopup(popupContent);

                  polyline_boundary.push(polilinea);
                }              
              }

              if(tipo=="geoJson" && (filtro == data.opcion || filtro == "TODO")){

                  polilinea = new L.geoJson(data.coordenadas).addTo(map);

                  polilinea.setStyle({capa: capa});
                  polilinea.setStyle({color: '#000000'});
                  polilinea.setStyle({filtro: data.opcion});

                  polyline_boundary.push(polilinea);
              }
            });
          },
          complete: function () {
            $("#spinnerData").hide();
          },
          error: function(data){
            new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
            $("#spinnerData").hide();
            console.log(data);
          }
      });
    }

    function clearInputs(){
      $("#selectDep").val("0");
      $("#selectProv").val("0");
      $("#selectDist").val("0");
      $("#colProv").hide();
      $("#colDist").hide();
      $('#selectfilter').bootstrapToggle('on')
      $("#selectTipoAlarma").val("1");
      $("#nombreMapa").val("");
    }

    function reloadMap(){
      sync = 1;
      reset();
    }

    function viewDataMap(dt) {
      $(".card-body h5").html("");
      $(".card-body .p-head").html("");
      $(".card-body .p-cant1").html("");
      $(".card-body .p-cant2").html("");
      $(".card-body .p-cant3").html("");
      $(".card-body .p-cant4").html("");
      $(".card-body .p-cant5").html("");

      $('#tabledata').hide();
      $('#tabledataTec').hide();
      $('#tabledataTec3').hide();
      $('#tabledataTec4').hide();
      $('#tabledataMbts').hide();
      $('#tabledataFija').hide();
      if(typeof dt != "undefined"){
        tipoCsv = "Fija";
        $.ajax({
            url: "/api_data.php",
            type: 'GET',
            //dataType: 'json',
            data: "id="+dt+"&type=fija&fecha="+$("#fechaMapa").val(),
            beforeSend:function () {
              $("#spinnerData").show();
            },
            success: function(data) {
              $("#csvDownload").hide();
              $("#myChart").hide();
              $("hr").hide();
              data = JSON.parse(data);

              $(".card-body h5").html("<b>"+dt+"</b></br></br>");
              $(".card-body .p-head").html("");
              jQuery.each( data[0], function( i, val ) {
                pos = i +1;
                $(".card-body .p-cant"+pos).html("<p><b>INCIDENCIA:</b> "+val.id_incidencia+"</p><p><b>FECHA INICIO:</b> "+val.fecha_creacion_incidente+"</p><p><b>CLIENTES CAIDOS:</b> "+val.clientes_caidos+"</p><p><b>HUB:</b> "+val.hub+"</p><p><b>RED:</b> "+val.red+"</p><p><b>ASIGNADO:</b> "+val.asignado+"</p><p><b>GRUPO ASIGNADO:</b> "+val.grupo_asignado+"</p><p><b>ELEMENTO DE RED AFECTADO:</b> "+val.elemento_red_afectado+"</p>");
              });
                

              $('.p-cant2').hide();
              $('.p-cant3').hide();
              $('.p-cant4').hide();
              $('.p-cant5').hide();

              $(".card").show();
            },
            complete: function () {
              $("#spinnerData").hide();
            },
            error: function(data){
              new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
              $("#spinnerData").hide();
              console.log(data);
            }
        });
      }
    }

    function changeDpto() {
      var CodeUbgDep = $("#selectDep").val();
      //var filtTypeMap = $(".slc-mapGran").val();
      if(CodeUbgDep!='0'){
        $.ajax({
          url: "http://172.17.27.157/portalregulatorio/api/mapData?param=AMAZONAS&type=selectDep",
          type: 'GET',
          dataType: 'json',
          data: "param="+CodeUbgDep+"&type=selectDep",
          beforeSend:function () {
            $("#spinnerData").show();
          },
          success: function(data) {
            var list = "<option selected value='0'>Todos</option>"
            $.each(data.data,function (x,y) {
              list +="<option value='"+y.provincia+"'>"+y.provincia+"</option>";              
            })
            $("#selectProv").html(list);
            $("#selectProv").removeAttr("disabled")
          },
          complete: function () {
              reset();
              $("#colProv").show();
              $("#spinnerData").hide();
          },
          error: function(data){
            new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
            $("#spinnerData").hide();
            console.log(data);
          }
        });

        // if(filtTypeMap=="555"){
          //fillColorByUbg(CodeUbgDep,1);
        // }
      }else{
        //fillColorByUbgInit();
        reset();
        $("#selectProv").html("<option selected value='0'>Todos</option>");
        $("#selectDist").html("<option selected value='0'>Todos</option>");
        $("#colProv").hide();
        $("#colDist").hide();
        //fillColorByUbg('',0,$(".slc-crit").val());
      }
    }

    function reset(){
      clearMarkers();
      if(dorsales_boundary.length>0){
        dorsales_boundary.forEach(dorsales => {
          dorsales.remove();
        });
      }
      /*if(polyline_boundary.length>0){
        polyline_boundary.forEach(dorsales => {
          dorsales.remove();
        });
      }*/
      if(fija_boundary.length>0){
        fija_boundary.forEach(planos => {
          planos.remove();
        });
      }
      if(fija_marcadores.length>0){
        fija_marcadores.forEach(marcadores => {
          marcadores.remove();
        });
      }
      if(sync == 1){
          loadDwdm = 0;
          loadDwdmAlarm = 0;
          loadRtn = 0;
          loadRtnAlarm = 0;
          loadMbts = 0;
          loadMbtsAlarm = 0;
          deleteMarkers();
      }
      if($("#dwdm").is(':checked')){
        if(sync == 1){
          loadDwdmMarkers($("#fechaMapa").val(),$("#nombreMapa").val());
        }else{
          loadDwdmMarkers($("#fechaMapa").val());
        }
      }
      if($("#rtnSites").is(':checked')){
        if(sync == 1){
          loadRtnMarkers($("#fechaMapa").val(),$("#nombreMapa").val());
        }else{
          loadRtnMarkers($("#fechaMapa").val());
        }
      }
      if($("#mbts").is(':checked')){
        if(sync == 1){
          loadMbtsMarkers($("#fechaMapa").val(),$("#nombreMapa").val());
        }else{
          loadMbtsMarkers($("#fechaMapa").val());
        }
      }
      if($("#fija").is(':checked')){
        if(sync == 1){
          fillColorPlano($("#fechaMapa").val(),$("#nombreMapa").val());
        }else{
          fillColorPlano($("#fechaMapa").val());
        }
      }
      if($("#dorsales").is(':checked')){
        loadCapa("dorsales","geoJson","TODO",$("#fechaMapa").val(),$("#nombreMapa").val());
      }
    }

    function changeProv() {
      var CodeUbgDep = $("#selectDep").val();
      var CodeUbgProv = $("#selectProv").val();

      if(CodeUbgProv!='0'){
        $.ajax({
          url: "http://172.17.27.157/portalregulatorio/api/mapData?param1=AMAZONAS&param2=BAGUA&type=selectProv",
          type: 'GET',
          dataType: 'json',
          data: "param1="+CodeUbgDep+"&param2="+CodeUbgProv+"&type=selectProv",
          beforeSend:function () {
            $("#spinnerData").show();
          },
          success: function(data) {
            var list = "<option selected value='0'>Todos</option>"
            $.each(data.data,function (x,y) {
              list +="<option value='"+y.distrito+"'>"+y.distrito+"</option>";              
            })
            $("#selectDist").html(list);
            $("#selectDist").removeAttr("disabled")
          },
          complete: function () {
              reset();
              $("#colDist").show();
              $("#spinnerData").hide();
          },
          error: function(data){
            new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
            $("#spinnerData").hide();
            console.log(data);
          }
        });


      }else{
        reset();

        $("#selectDist").html("<option selected value='0'>Todos</option>");
        $("#colDist").hide();

      }
    }
    
    // Initialize and add the map
    function initMap() {

        // The location of lima
        const lima = {
            lat: -10.0431805,
            lng: -74.0282364
        };

        // The map, centered at lima
        /*map = new google.maps.Map(document.getElementById("map"), {
            zoom: 5.8,
            center: lima,
            minZoom: 4,
            disableDefaultUI: true,
        });*/

        map = L.map('map').setView(lima, 5.8);

        L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 19,
            minZoom: 4,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(map);

        //lasso = L.lasso(map, { position: 'topleft', title: 'Seleccionar marcadores'});

        lassoControl = L.control.lasso({ position: 'topleft', title: 'Seleccionar marcadores', callback: function(bounds){console.log(bounds);}}).addTo(map);

        //initZoomControl(map);
        //initMapTypeControl(map);
        //initFullscreenControl(map);

        /*measureTool = new MeasureTool(map, {
          contextMenu: false
          // some other options...
        });*/

        /*oms = new OverlappingMarkerSpiderfier(map, {
          markersWontMove: true,
          markersWontHide: true,
          basicFormatEvents: true,
          nearbyDistance:1,
          keepSpiderfied:true
        });*/
          
        const iconBase = "/portalmonitoreonoc/images/";

        icons = {
          tec:{
            icon: iconBase + "mbts3.png?rand="+Math.random(),
          },
          tec2:{
            icon: iconBase + "mbts3-anaranjado.png?rand="+Math.random(),
          },
          tec3:{
            icon: iconBase + "mbts3-rojo.png?rand="+Math.random(),
          },
          tec4:{
            icon: iconBase + "mbts3-amarillo.png?rand="+Math.random(),
          },
          rtn1:{
            icon: iconBase + "rtn-1.png?rand="+Math.random(),
          },
          rtn2:{
            icon: iconBase + "rtn-2.png?rand="+Math.random(),
          },
          rtn3:{
            icon: iconBase + "rtn-3.png?rand="+Math.random(),
          },
          dwdm: {
            icon: iconBase + "dwdm.png?rand="+Math.random(),
          },
          sites: {
            icon: iconBase + "black.png?rand="+Math.random(),
          },
          nodal: {
            icon: iconBase + "black.png?rand="+Math.random(),
          },
          alarma1: {
            icon: iconBase + "dwdm-yellow.png?rand="+Math.random(),
          },
          alarma2: {
            icon: iconBase + "dwdm-orange.png?rand="+Math.random(),
          },
          alarma3: {
            icon: iconBase + "dwdm-red.png?rand="+Math.random(),
          },
        };        

        /*map.data.forEach(function (feature) {
          map.data.remove(feature);
        });*/

        buildCustomIconMarkerCluster();

        pruneCluster.BuildLeafletClusterIcon = function (cluster) {
                var e = new L.Icon.MarkerCluster();
                e.stats = cluster.stats;
                e.population = cluster.population;
                return e;
            };

        pruneCluster.Cluster.Size = 50;

        //loadDwdmMarkers($("#fechaMapa").val());

        loadCapa('enlaces_dorsales_fo','polylineMulti','TODO');

        reset();

        map.on('lasso.finished', event => {
          setSelectedLayers(event.layers);
          //lasso.enable();
        });

        //planosDorsales();  

        //showMarkers();

        //$("#spinnerData").hide();

        // Add a marker clusterer to manage the markers.
        /* new MarkerClusterer(map, markers, {
          imagePath:
            "https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m",
        }); */

    }
    
    function initZoomControl(map) {
      document.querySelector(".zoom-control-in").onclick = function () {
        map.setZoom(map.getZoom() + 1);
      };

      document.querySelector(".zoom-control-out").onclick = function () {
        map.setZoom(map.getZoom() - 1);
      };

      map.controls[google.maps.ControlPosition.LEFT_TOP].push(
        document.querySelector(".zoom-control")
      );
    }

    function initMapTypeControl(map) {
      const mapTypeControlDiv = document.querySelector(".maptype-control");

      document.querySelector(".maptype-control-map").onclick = function () {
        mapTypeControlDiv.classList.add("maptype-control-is-map");
        mapTypeControlDiv.classList.remove("maptype-control-is-satellite");
        map.setMapTypeId("roadmap");
      };

      document.querySelector(".maptype-control-satellite").onclick = function () {
        mapTypeControlDiv.classList.remove("maptype-control-is-map");
        mapTypeControlDiv.classList.add("maptype-control-is-satellite");
        map.setMapTypeId("hybrid");
      };

      map.controls[google.maps.ControlPosition.LEFT_TOP].push(mapTypeControlDiv);
    }

    function initFullscreenControl(map) {
      const elementToSendFullscreen = map.getDiv().firstChild;
      const fullscreenControl = document.querySelector(".fullscreen-control");

      map.controls[google.maps.ControlPosition.RIGHT_TOP].push(fullscreenControl);
      fullscreenControl.onclick = function () {
        if (isFullscreen(elementToSendFullscreen)) {
          exitFullscreen();
        } else {
          requestFullscreen(elementToSendFullscreen);
        }
      };

      document.onwebkitfullscreenchange =
        document.onmsfullscreenchange =
        document.onmozfullscreenchange =
        document.onfullscreenchange =
          function () {
            if (isFullscreen(elementToSendFullscreen)) {
              fullscreenControl.classList.add("is-fullscreen");
            } else {
              fullscreenControl.classList.remove("is-fullscreen");
            }
          };
    }

    function isFullscreen(element) {
      return (
        (document.fullscreenElement ||
          document.webkitFullscreenElement ||
          document.mozFullScreenElement ||
          document.msFullscreenElement) == element
      );
    }

    function requestFullscreen(element) {
      if (element.requestFullscreen) {
        element.requestFullscreen();
      } else if (element.webkitRequestFullScreen) {
        element.webkitRequestFullScreen();
      } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
      } else if (element.msRequestFullScreen) {
        element.msRequestFullScreen();
      }
    }

    function exitFullscreen() {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
      } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
      }
    }

    // Adds a marker to the map and push to the array.
    function addMarkerCVM(feature,icon,type) {

      if($("#nombreMapa").val() != "" && sync == 1){
        texto = feature.id;
        texto = texto.toLowerCase();
        if(texto.indexOf($("#nombreMapa").val().toLowerCase()) >= 0){
          addMarkerFilter(feature,icon,type);
        }
      }else{
        addMarkerFilter(feature,icon,type);
      }
    }

    function medir_regla(feature){
      if(ruler_event == 1){
        ruler_event = 0;
        measureTool.end();
        measureTool.start([{lat: feature.latitude, lng: feature.longitude},{lat: temp_lat, lng: temp_lng}]);
      }else{
        ruler_event = 1;
        measureTool.end();
        temp_lat = feature.latitude;
        temp_lng = feature.longitude;
        measureTool.start([{lat: feature.latitude, lng: feature.longitude}]);
      }
    }

    function buildCustomIconMarkerCluster() {
      var self = this;
      var cluster_size = 48;
      var cluster_size_half = cluster_size / 2;
      var marker_group_1 = 0;
      var marker_group_2 = 0;

      L.Icon.MarkerCluster = L.Icon.extend({
          options: {
              iconSize: new L.Point(cluster_size, cluster_size),
              className: 'prunecluster leaflet-markercluster-icon',
          },

          createIcon: function () {

              // based on L.Icon.Canvas from shramov/leaflet-plugins (BSD licence)
              var e = document.createElement('canvas');
              this._setIconStyles(e, 'icon');
              var s = this.options.iconSize;
              e.width = s.x;
              e.height = s.y;
              this.draw(e.getContext('2d'), s.x, s.y);
              return e;
          },

          createShadow: function () {
              return null;
          },

          draw: function (canvas, width, height) {
              var showAll = document.getElementById('selectfilter').checked;
              var lol = 0;
              var start = 0;
              var colors = self.getColors();
              marker_group_1 = marker_group_2 = 0;

              for (var i = 0, l = colors.length; i < l; ++i) {
                  if (i == 1 ) {
                    marker_group_2 += this.stats[i];
                  }
                  if (showAll){
                    if (i == 0 ) {
                      marker_group_1 += this.stats[i];
                    }
                  }
              }

              for (i = 0, l = colors.length; i < l; ++i) {
                  //var is100G = i == 3;
                  //var _p = is100G ? this.population - marker_group_1 : this.population - marker_group_2;
                  var is100G = true;
                  var _p = 0;
                  if (i == 1 ) {
                    if(marker_group_1 != marker_group_2)
                      _p =  this.population - marker_group_2;
                    else
                      _p =  (this.population - marker_group_2) * 2;
                    
                  }
                  if (showAll){
                    if (i == 0 ) {
                      _p =  this.population - marker_group_1;
                    }
                  }

                  if(_p != 0)
                    var size = this.stats[i] / this.population ;
                  else
                    var size = 1;

                  if (size >= 0) {
                      canvas.beginPath();
                      if (is100G) {
                          canvas.moveTo(24, 24);
                          var from = start + 0.14,
                              to = start + size * pi2;

                          if (to < from) {
                              from = start;
                          }
                          
                          canvas.arc(24, 24, 20, from, to);
                          start = start + size * pi2;
                          canvas.lineTo(24, 24);
                          canvas.fillStyle = colors[i];
                          canvas.fill();
                          canvas.closePath();
                      }
                      else {
/* 
                          var angle = Math.PI / 4 * i;
                          var posx = Math.cos(angle) * 18, posy = Math.sin(angle) * 18;
                          var xa = 0, xb = 1, ya = 4, yb = 8;
                          // var r = ya + (size - xa) * ((yb - ya) / (xb - xa));
                          var r = ya + size * (yb - ya);
                          //canvas.moveTo(posx, posy);
                          canvas.arc(cluster_size_half + posx, cluster_size_half + posy, r, 0, pi2);

                          canvas.fillStyle = colors[i];
                          canvas.fill();
                          canvas.closePath();

                          canvas.beginPath();
                          //canvas.arc(cluster_size_half + posx, cluster_size_half + posy, r, 0, pi2);
                          canvas.strokeStyle = '#000000';
                          canvas.stroke();
                          canvas.closePath(); */

                      }
                  }
              }
              canvas.beginPath();
              canvas.fillStyle = 'white';
              canvas.arc(cluster_size_half, cluster_size_half, 14, 0, Math.PI * 2);
              canvas.fill();
              canvas.closePath();
              canvas.fillStyle = '#555';
              canvas.textAlign = 'center';
              canvas.textBaseline = 'middle';
              canvas.font = 'bold 10px sans-serif';     
                       

              var _slashed_population = '';

              if (showAll)
                  _slashed_population = marker_group_1 + '/' + marker_group_2;
              else
                  _slashed_population = this.population;

              canvas.fillText(_slashed_population, cluster_size_half, cluster_size_half, cluster_size);
          }
      });
    }

    function getColors() {
      return [
          '#008000',
          '#FF0000'
      ];
    }

    function addMarkerFilter(feature,icon,type){

      if(feature.estado !== undefined){
        var estado = feature.estado;
      }else{
        var estado = "0";
      }

      var marker = new PruneCluster.Marker(feature.latitude,feature.longitude,{
            //clickable: true,
            //position: new google.maps.LatLng(feature.latitude,feature.longitude),
            id: feature.id,
            icon:icon,
            type: type,
            dep: feature.dep,
            prov: feature.prov,
            dist: feature.dist
      });

      marker.category = setMarkerCategory(estado);

      //pruneCluster.RegisterMarker(marker);

      pruneCluster.PrepareLeafletMarker = function(leafletMarker, marker) {  

          leafletMarker.setIcon(L.icon({
                          iconUrl:marker.icon,
                          id: marker.id
                          //iconSize: [25, 25]
                      }));
          leafletMarker.on('click', function(){
            //do click event logic here
            if(ruler == 1){
              medir_regla(feature);
            }else{
              $(".card-body h5").html("");
              $(".card-body .p-head").html("");
              $(".card-body .p-cant1").html("");
              $(".card-body .p-cant2").html("");
              $(".card-body .p-cant3").html("");
              $(".card-body .p-cant4").html("");
              $(".card-body .p-cant5").html("");

              $('#tabledata').hide();
              $('#tabledataTec').hide();
              $('#tabledataTec3').hide();
              $('#tabledataTec4').hide();
              $('#tabledataMbts').hide();
              $('#tabledataFija').hide();

              if(marker.type == 'dwdm' || marker.type == 'alarma'){
                tipoCsv = "Dwdm";
                $.ajax({
                  url: "/api_data.php",
                  type: 'GET',
                  //dataType: 'json',
                  data: "id="+marker.id+"&type=dwdm&fecha="+$("#fechaMapa").val(),
                  beforeSend:function () {
                    $("#spinnerData").show();
                  },
                  success: function(data) {
                    try{        
                      data = JSON.parse(data);            
                      $("#csvDownload").hide();
                      $("#myChart").hide();
                      $("hr").hide();

                      if(data[0].length > 0){
                        
                        $(".card-body h5").html("<b>"+marker.id+"</b></br></br>");
                        $(".card-body .p-head").html("<b>Zona:</b> "+data[0][0].zona+"</p><p><b>Ubigeo:</b> "+data[0][0].ubigeo+"<p><b>Incidencia:</b> "+data[0][0].incidencia);
                        jQuery.each( data[0], function( i, val ) { 
                            pos = i+1;
                            $(".card-body .p-cant"+pos).html("<div class='text-danger font-weight-bold font-italic'>"+val.alarmname+"</div>");
                            $(".card-body .p-cant"+pos).append("<p><b>Ultima Alarma:</b> "+val.occurrencetime+"</p><p><b>Alarm Source (Tramo):</b> "+val.locationinformation+"</p>");
                        });
                      }else{
                        $(".card-body h5").html("<b>"+marker.id+"</b></br></br>");
                      }

                      $('.p-cant2').show();
                      $('.p-cant3').show();
                      $('.p-cant4').hide();
                      $('.p-cant5').hide();

                      $(".card").show();
                    } catch(e){
                      new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                      $("#spinnerData").hide();
                      console.log(e);
                    }
                  },
                  complete: function () {
                      $("#spinnerData").hide();
                  },
                  error: function(data){
                    new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                    $("#spinnerData").hide();
                    console.log(data);
                  }
                });
              }
              if(marker.type == 'rtn_alarmas' || marker.type == 'sites' || marker.type == 'nodal'){
                tipoCsv = "Rtn";
                $.ajax({
                  url: "/api_data.php",
                  type: 'GET',
                  //dataType: 'json',
                  data: "id="+marker.id+"&type=rtn&fecha="+$("#fechaMapa").val(),
                  beforeSend:function () {
                    $("#spinnerData").show();
                  },
                  success: function(data) {
                      try{
                      data = JSON.parse(data);
                      $("#csvDownload").hide();
                      $("#myChart").hide();
                      $("hr").hide();
                      if(data[0].length > 0){
                        $(".card-body h5").html("<b>"+marker.id+"</b></br></br>");
                        $(".card-body .p-head").html("<b>Ubigeo:</b> "+data[0][0].ubigeo+"</p><p><b>Tipo Elemento:</b> "+data[0][0].ne_type);
                        jQuery.each( data[0], function( i, val ) { 
                            pos = i+1;
                            $(".card-body .p-cant"+pos).html("<div class='text-danger font-weight-bold font-italic'>"+val.alarmname+"</div>");
                            $(".card-body .p-cant"+pos).append("<p><b>Ultima Alarma:</b> "+val.occurrencetime+"</p>");                      
                        });
                      }else{
                        $(".card-body h5").html("<b>"+marker.id+"</b></br></br>");
                      }

                      $('.p-cant2').show();
                      $('.p-cant3').hide();
                      $('.p-cant4').hide();
                      $('.p-cant5').hide();

                      $(".card").show();
                    } catch(e){
                      new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                      $("#spinnerData").hide();
                      console.log(e);
                    }
                  },
                  complete: function () {
                      $("#spinnerData").hide();
                  },
                  error: function(data){
                    new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                    $("#spinnerData").hide();
                    console.log(data);
                  }
                });
              }        
              if(marker.type == 'mbts_activas' || marker.type == 'mbts' || marker.type == 'mbts_energia'){
                tipoCsv = "MBTS";
                $.ajax({
                  url: "/api_data.php",
                  type: 'GET',
                  //dataType: 'json',
                  data: "id="+marker.id+"&type=mbts&fecha="+$("#fechaMapa").val(),
                  beforeSend:function () {
                    $("#spinnerData").show();
                  },
                  success: function(data) {
                      try{
                      $("#csvDownload").hide(); 
                      $("#myChart").hide();
                      $("hr").hide();
                      data = JSON.parse(data);
                      if(data[0].length > 0){
                        $(".card-body h5").html("<b>"+marker.id+"</b></br></br>");
                        $(".card-body .p-head").html("<b>Jefatura Radio:</b> "+data[0][0].jefatura+"</p><p><b>Ubigeo:</b> "+data[0][0].ubigeo+"<p><b>Incidencia:</b> "+data[0][0].incidencia+"</p><p><b>Red:</b> "+data[0][0].red+"</p><p><b>Tecnologías Caídas:</b> "+data[0][0].tecnologia_caida);
                        jQuery.each( data[0], function( i, val ) { 
                          pos = i+1;
                          $(".card-body .p-cant"+pos).html("<div class='text-danger font-weight-bold font-italic'>"+val.alarmname+"</div>");
                          $(".card-body .p-cant"+pos).append("<p><b>Ultima Alarma:</b> "+val.occurrencetime+"</p><p><b>Tipo Caida:</b> "+val.tipo_caida+"</p>");
                          
                        });
                      }else{
                        $(".card-body h5").html("<b>"+marker.id+"</b></br></br>");
                      }

                      $('.p-cant2').show();
                      $('.p-cant3').show();
                      $('.p-cant4').show();
                      $('.p-cant5').show();

                      $(".card").show();
                    } catch(e){
                      new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                      $("#spinnerData").hide();
                      console.log(e);
                    }
                  },
                  complete: function () {
                      $("#spinnerData").hide();
                  },
                  error: function(data){
                    new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
                    $("#spinnerData").hide();
                    console.log(data);
                  }
                });
              }
            }
          });
        };       
        markers.push(marker);
    }

    function setMarkerCategory(tipo_alarma) {
        switch (tipo_alarma) {
            case "3": case "4":
                return 1;
            case "0":
                return 0;
            case 'dwdm': case 'alarma':
                return 3;
            case 'rtn_alarmas': case 'sites': case 'nodal':
                return 4;
            case 'mbts_activas': case 'mbts': case 'mbts_energia':
                return 5;
            case null:
                return 0;
            default:
                return 0;
        }
    }

    // Sets the map on all markers in the array.
    function setMapOnAll(map) {
      for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(map);
        if(map){
          //oms.addMarker(markers[i]);
        }
      }
    }

    // Sets the map on all markers in the array.
    function setMapOnSites(map,type) {
      selectDep = $("#selectDep").val();
      selectProv = $("#selectProv").val();
      selectDist = $("#selectDist").val();
      for (let i = 0; i < markers.length; i++) {
        if(selectDep=="0"){
          if(markers[i].data.type == type){
            pruneCluster.RegisterMarker(markers[i]);
            //markers[i].setMap(map);
          }
        }else{
          if(selectProv=="0"){
            if(markers[i].data.type == type && markers[i].data.dep == selectDep){
              pruneCluster.RegisterMarker(markers[i]);
              //markers[i].setMap(map);
            }
          }else{
            if(selectDist=="0"){
              if(markers[i].data.type == type && markers[i].data.dep == selectDep && markers[i].data.prov == selectProv){
                pruneCluster.RegisterMarker(markers[i]);
                //markers[i].setMap(map);
              }
            }else{
              if(markers[i].data.type == type && markers[i].data.dep == selectDep && markers[i].data.prov == selectProv && markers[i].data.dist == selectDist){
                pruneCluster.RegisterMarker(markers[i]);
                //markers[i].setMap(map);
              }
            }
          }
        }
        //oms.addMarker(markers[i]);
      }

      map.addLayer(pruneCluster);
      pruneCluster.ProcessView();

      for (let i = 0; i < listPolyBase.length; i++) {
        if(listPolyBase[i].type == type){
          listPolyBase[i].setMap(map);
        }
      }
    }

    // Removes the markers from the map, but keeps them in the array.
    function clearMarkers() {
      //setMapOnAll(null);
      //oms.removeAllMarkers()
      pruneCluster.RemoveMarkers(markers);
      pruneCluster.ProcessView();
    }

    // Removes the markers from the map, but keeps them in the array.
    function clearMarkersSites(type) {
      var markers_temp = [];
      for (let i = 0; i < markers.length; i++) {
        if(markers[i].data.type == type){          
          markers_temp.push(markers[i]);
        }
      }
      pruneCluster.RemoveMarkers(markers_temp);
      pruneCluster.ProcessView();
    }

    // Removes the markers from the map, but keeps them in the array.
    function clearMarkersId(id) {
      var markers_temp = [];
      for (let i = 0; i < markers.length; i++) {
        if(markers[i].data.id == id){          
          markers_temp.push(markers[i]);
        }
      }
      pruneCluster.RemoveMarkers(markers_temp);
      pruneCluster.ProcessView();
    }

    // Shows any markers currently in the array.
    function showMarkers() {
      setMapOnAll(map);
    }

    // Deletes all markers in the array by removing references to them.
    function deleteMarkers() {
      clearMarkers();
      markers = [];
    }

    /*function exportarCapa(capa){
      $("#spinnerData").show();
      var id_lasso_temp = id_lasso;
      var currentDate = new Date();
      var formattedDate = currentDate.getFullYear() +
          ("0" + (currentDate.getMonth() + 1)).slice(-2) +
          ("0" + currentDate.getDate()).slice(-2) +
          ("0" + currentDate.getHours()).slice(-2) +
          ("0" + currentDate.getMinutes()).slice(-2) +
          ("0" + currentDate.getSeconds()).slice(-2);
      $.ajax({
        url: "{{ "route('api.capaReport')" }}",
        type: 'GET',
        data: "capa="+capa+"&fecha="+$("#fechaMapa").val()+"&id_lasso="+id_lasso_temp.join(),
        xhrFields: { responseType: "blob" },
        success: function (data) {
            var a = document.createElement("a");
            var url = window.URL.createObjectURL(data);
            a.href = url;
            a.download = "Lista_Elementos_Afectados_"+capa+"_"+formattedDate+".xls";
            document.body.append(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            $("#spinnerData").hide();
        },
        error: function(data){
          new Noty({text: "Ocurrió un error al cargar la información", type: "warning", timeout: 2000}).show();
          $("#spinnerData").hide();
          console.log(data);
        }
      });
    }*/

    function tableToCSV() {
 
      // Variable to store the final csv data
      var csv_data = [];

      // Get each row data
      var rows = document.getElementsByClassName('data-table');

      for (var i = 0; i < rows.length; i++) {

          // Get each column data
          var cols = rows[i].querySelectorAll('td,th');

          // Stores each csv row data
          var csvrow = [];
          for (var j = 0; j < cols.length; j++) {

              // Get the text data of each cell of
              // a row and push it to csvrow
              csvrow.push(cols[j].innerHTML);
          }

          // Combine each column value with comma
          csv_data.push(csvrow.join(","));
      }
      // combine each row data with new line character
      csv_data = csv_data.join('\n');

      /* We will use this function later to download
      the data in a csv file downloadCSVFile(csv_data);
      */
     // Call this function to download csv file 
     downloadCSVFile(csv_data);
    }

    function downloadCSVFile(csv_data) {
 
      // Create CSV file object and feed our
      // csv_data into it
      CSVFile = new Blob([csv_data], { type: "text/csv" });

      // Create to temporary link to initiate
      // download process
      var temp_link = document.createElement('a');

      // Download csv file
      temp_link.download = "alarma_"+tipoCsv+"_"+$.datepicker.formatDate('ddmmyy', new Date())+".csv";
      var url = window.URL.createObjectURL(CSVFile);
      temp_link.href = url;

      // This link should not be displayed
      temp_link.style.display = "none";
      document.body.appendChild(temp_link);

      // Automatically click the link to trigger download
      temp_link.click();
      document.body.removeChild(temp_link);
    }

</script>
<!-- JavaScript Bundle with Popper -->  
<!-- <script src="https://unpkg.com/@google/markerclustererplus@4.0.1/dist/markerclustererplus.min.js"></script> -->
<!-- Async script executes immediately and must be after any DOM elements used in callback. -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD2_aylDeV0Y-nijy4TWemxJ_QCcjLRYHA&callback=initMap&libraries=drawing,geometry&v=weekly" async></script>
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js"></script> -->
  @endsection